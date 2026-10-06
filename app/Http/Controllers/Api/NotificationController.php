<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The notification bell (every signed-in user) and the staff Messages inbox.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Please sign in.');

        $items = UserNotification::where('user_id', $user->id)->orderByDesc('created_at')->orderByDesc('id')->limit(30)->get();

        return response()->json([
            'unread' => UserNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
            'unreadMessages' => $this->unreadMessageCount($request),
            'items' => $items->map(fn (UserNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'link' => $n->link,
                'read' => (bool) $n->read_at,
                'createdAt' => $n->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    /** Mark one notification (id) or all of them as read. */
    public function markRead(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Please sign in.');
        $data = $request->validate(['id' => ['nullable', 'integer']]);

        UserNotification::where('user_id', $user->id)
            ->when($data['id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** Staff inbox: every order that has messages, newest first, with unread counts. */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->isStaff(), 403, 'Staff only.');

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
                ->whereHas('order', fn ($q) => $q->where(fn ($q) => $q->whereNull('handled_by')->orWhere('handled_by', $user->id))
                    ->whereHas('customer', fn ($c) => $c->where('user_id', '!=', $user->id)))
                ->count();
        }

        return OrderMessage::fromStaff()->whereNull('read_at')
            ->whereHas('order.customer', fn ($q) => $q->where('user_id', $user->id))
            ->count();
    }
}
