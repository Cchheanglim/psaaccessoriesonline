<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\Payment;
use App\Models\User;
use App\Support\Storefront;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The notification bell (every signed-in user) and the staff Messages inbox.
 * Nothing is stored for the bell: it is built from orders, status changes, payments and messages.
 */
class NotificationController extends Controller
{
    /** How far back the bell looks. */
    private const DAYS = 30;

    /**
     * The bell, worked out from what is already stored (orders, their status history, payments
     * and messages); nothing is saved for it. What counts as unread is decided by the browser,
     * which remembers when the bell was last opened and sends it as ?since=.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Please sign in.');
        $data = $request->validate(['since' => ['nullable', 'date']]);
        $since = isset($data['since']) ? Carbon::parse($data['since']) : null;

        $items = ($user->isStaff() ? $this->staffFeed($user) : $this->customerFeed($user))
            ->sortByDesc(fn ($n) => [$n['at']->getTimestamp(), $n['rank'], $n['seq']])
            ->take(30)->values();

        return response()->json([
            'unread' => $items->filter(fn ($n) => ! $since || $n['at']->gt($since))->count(),
            'unreadMessages' => $this->unreadMessageCount($request),
            'items' => $items->map(fn ($n) => [
                'id' => $n['id'],
                'type' => $n['type'],
                'title' => $n['title'],
                'body' => $n['body'],
                'link' => $n['link'],
                'read' => $since !== null && ! $n['at']->gt($since),
                'createdAt' => $n['at']->toIso8601String(),
            ])->all(),
        ]);
    }

    /** A customer: their orders being approved, sent out, delivered or cancelled, slips sent back, and replies. */
    private function customerFeed(User $user): Collection
    {
        $from = now()->subDays(self::DAYS);
        $orders = Order::with(['statusHistory', 'payments', 'messages', 'customer', 'approval.changedBy:id,name', 'latestPayment.method'])
            ->where('user_id', $user->id)
            ->latest()->limit(50)->get();

        $feed = collect();
        foreach ($orders as $order) {
            $number = $order->order_number;
            $link = 'order-detail.html?order='.rawurlencode($number);
            $contact = $order->handler ? explode(' ', trim($order->handler->name))[0] : 'our team';
            foreach ($order->statusHistory as $h) {
                $code = $h->status;
                if ($code === 'pending_payment' || $h->created_at < $from) {
                    continue; // the customer placed it themselves
                }
                [$title, $body, $hash] = match ($code) {
                    'processing' => [$order->payment_method === 'cod' ? 'Order confirmed' : 'Payment confirmed',
                        "{$contact} approved order {$number} and it's being prepared. You can message {$contact} from your order page.", ''],
                    'out_for_delivery' => ['Out for delivery', "Order {$number} is on its way to you.", ''],
                    'delivered' => ['Delivered', "Order {$number} was delivered. Enjoy! You can review your items now.", '#review'],
                    'cancelled' => ['Order cancelled', "Order {$number} was cancelled. Message us if you have questions.", ''],
                    default => [null, null, ''],
                };
                if ($title) {
                    $feed->push($this->item('h'.$h->id, 'order_status', $title, $body, $link.$hash, $h->created_at, 1, $h->id));
                }
            }
            foreach ($order->payments->where('status', 'failed') as $p) {
                if ($p->updated_at >= $from) {
                    $feed->push($this->item('p'.$p->id, 'order_status', 'Please send a new payment slip',
                        "We couldn't verify the slip for {$number}. Check the amount and upload the correct screenshot.", $link, $p->updated_at, 2, $p->id));
                }
            }
            foreach ($order->messages as $m) {
                if ($m->created_at >= $from && $m->isFromStaff($order)) {
                    $feed->push($this->item('m'.$m->id, 'message', "New message about order {$number}",
                        mb_strimwidth($m->body, 0, 140, '…'), $link.'#messages', $m->created_at, 3, $m->id));
                }
            }
        }

        return $feed;
    }

    /**
     * Staff: what their role can act on. New orders and slips go to everyone who can approve
     * payments until someone picks the order up, then to that person; customer messages the same way.
     */
    private function staffFeed(User $user): Collection
    {
        $from = now()->subDays(self::DAYS);
        $mineOrOpen = fn ($q) => $q->whereDoesntHave('approval')->orWhereHas('approval', fn ($a) => $a->where('changed_by', $user->id));
        $feed = collect();

        if ($user->hasPermission('verify_payments') || $user->hasPermission('manage_orders')) {
            $orders = Order::with(['items', 'customer', 'address', 'latestPayment.method', 'promoCode'])
                ->where('created_at', '>=', $from)->where('user_id', '!=', $user->id)
                ->latest()->limit(30)->get();
            foreach ($orders as $o) {
                $feed->push($this->item('o'.$o->id, 'order_placed', "New order {$o->order_number}",
                    sprintf('%s ordered %d item(s), $%s by %s.', $o->customer_name, $o->items->sum('quantity'), number_format((float) $o->total_usd, 2), Storefront::paymentName($o->payment_method)),
                    $this->staffLink($o), $o->created_at, 0, $o->id));
            }
        }

        if ($user->hasPermission('verify_payments')) {
            $slips = Payment::with(['order.customer', 'order.address'])->where('status', 'slip_uploaded')->where('updated_at', '>=', $from)
                ->whereHas('order', $mineOrOpen)->latest('updated_at')->limit(30)->get();
            foreach ($slips as $p) {
                $o = $p->order;
                $feed->push($this->item('p'.$p->id, 'slip_uploaded', "Payment slip for {$o->order_number}",
                    "{$o->customer_name} uploaded a slip for $".number_format((float) $p->amount_usd, 2).'. Check it and approve.',
                    $this->staffLink($o), $p->updated_at, 2, $p->id));
            }
        }

        if ($user->hasPermission('manage_messages')) {
            $messages = OrderMessage::with(['order.customer', 'order.address'])->fromCustomer()->where('created_at', '>=', $from)
                ->where('sender_id', '!=', $user->id)->whereHas('order', $mineOrOpen)->latest()->limit(30)->get();
            foreach ($messages as $m) {
                $o = $m->order;
                $feed->push($this->item('m'.$m->id, 'message', "{$o->customer_name} sent a message ({$o->order_number})",
                    mb_strimwidth($m->body, 0, 140, '…'), $this->staffLink($o).'#messages', $m->created_at, 3, $m->id));
            }
        }

        return $feed;
    }

    private function staffLink(Order $order): string
    {
        return 'order-detail--admin-payment-submitted.html?order='.rawurlencode($order->order_number);
    }

    /** One bell entry; rank and seq order entries that happened in the same second. */
    private function item(string $id, string $type, string $title, ?string $body, string $link, $at, int $rank, int $seq): array
    {
        return compact('id', 'type', 'title', 'body', 'link', 'at', 'rank', 'seq');
    }

    /** Staff inbox: every order that has messages, newest first, with unread counts. */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->isStaff() && $user->hasPermission('manage_messages'), 403, 'Your role does not have the "Customer messages" permission.');

        $orders = Order::with(Order::PAGE_RELATIONS)
            ->whereHas('messages')
            ->withCount(['messages as unread_count' => fn ($q) => $q->fromCustomer()->whereNull('read_at')])
            ->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')
            ->limit(100)
            ->get();

        $last = OrderMessage::whereIn('order_id', $orders->pluck('id'))
            ->orderByDesc('created_at')->orderByDesc('id')->get()->unique('order_id')->keyBy('order_id');

        return response()->json([
            'conversations' => $orders->map(fn (Order $o) => [
                'orderId' => $o->order_number,
                'customerName' => $o->customer_name,
                'status' => \App\Support\Storefront::statusLabel($o),
                'handledBy' => $o->handler?->name,
                'mine' => $o->handled_by === $user->id,
                'unread' => (int) $o->unread_count,
                'lastMessage' => mb_strimwidth((string) $last->get($o->id)?->body, 0, 120, '…'),
                'lastFromStaff' => (bool) $last->get($o->id)?->isFromStaff($o),
                'lastAt' => optional($last->get($o->id)?->created_at)->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    /** Customers: shop replies they haven't opened. Staff: customer messages on orders they handle or nobody handles yet. */
    private function unreadMessageCount(Request $request): int
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return OrderMessage::fromCustomer()->whereNull('read_at')
                ->whereHas('order', fn ($q) => $q->where(fn ($q) => $q->whereDoesntHave('approval')->orWhereHas('approval', fn ($a) => $a->where('changed_by', $user->id)))
                    ->where('user_id', '!=', $user->id))
                ->count();
        }

        return OrderMessage::fromStaff()->whereNull('read_at')
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->count();
    }
}
