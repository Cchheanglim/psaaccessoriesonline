<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Support\Notifier;
use App\Support\Storefront;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Session-based JSON API used by the storefront pages in public/*.html.
 */
class StoreApiController extends Controller
{
    /**
     * Everything a page needs on load: the signed-in user, catalog,
     * payment methods, and the orders/users this user may see.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->status === 'Suspended') {
            $this->endSession($request);
            $user = null;
        }

        $isStaff = $user?->isStaff() ?? false;
        $guestOrders = $request->session()->get('guest_orders', []);

        // The catalog is the same for every visitor, so keep it briefly (Supabase round trips are slow).
        $catalog = Storefront::catalog($isStaff);

        $orders = (! $isStaff && ! $user && ! $guestOrders) ? collect() : Order::with(['items', 'handler:id,name'])
            ->withCount(['messages', 'reviews', 'messages as unread_replies_count' => fn ($q) => $q->where('from_staff', true)->whereNull('read_at')])
            ->when(! $isStaff, function ($q) use ($user, $guestOrders) {
                $q->where(function ($q) use ($user, $guestOrders) {
                    $q->whereIn('order_number', $guestOrders);
                    if ($user) {
                        $q->orWhere('user_id', $user->id);
                    }
                });
            })
            ->latest()
            ->get();

        return response()->json([
            'csrf' => csrf_token(),
            'user' => $user ? Storefront::user($user) : null,
            'products' => $catalog['products'],
            'paymentMethods' => $catalog['paymentMethods'],
            'orders' => $orders->map(fn ($o) => Storefront::order($o))->values(),
            'users' => $user?->isAdmin()
                ? User::orderBy('id')->get()->map(fn ($u) => Storefront::user($u))->values()
                : [],
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'min:3', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Email or phone number is required.',
            'login.min' => 'Please enter a valid email or phone number.',
            'password.required' => 'Password is required.',
        ]);

        $login = trim($data['login']);
        $user = User::where('email', Str::lower($login))
            ->orWhere('phone', $login)
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'Wrong email/phone or password.']);
        }

        if ($user->status === 'Suspended') {
            throw ValidationException::withMessages(['login' => 'This account is suspended. Contact the store admin.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json(['user' => Storefront::user($user), 'csrf' => csrf_token()]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:8', 'max:30', 'regex:/^[+0-9\s\-()]+$/'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ], [
            'name.required' => 'Full name is required.',
            'name.min' => 'Full name must be at least 2 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone.required' => 'Phone number is required.',
            'phone.min' => 'Phone number must be at least 8 digits.',
            'phone.regex' => 'Please enter a valid phone number (digits, spaces, or +).',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => 'buyer',
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json(['user' => Storefront::user($user), 'csrf' => csrf_token()], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->endSession($request);

        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'min:8', 'max:30', 'regex:/^[+0-9\s\-()]+$/'],
            'address' => ['sometimes', 'nullable', 'string', 'min:5', 'max:255'],
            'photo' => ['sometimes', 'nullable', 'string', 'max:3000000', Storefront::imageRule()],
            'banner' => ['sometimes', 'nullable', 'string', 'max:4000000', Storefront::imageRule()],
        ], [
            'name.required' => 'Full name is required.',
            'name.min' => 'Full name must be at least 2 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already in use by another account.',
            'phone.regex' => 'Please enter a valid phone number.',
            'address.min' => 'Delivery address must be at least 5 characters.',
        ]);

        if (array_key_exists('photo', $data)) {
            $data['avatar'] = $data['photo'];
            unset($data['photo']);
        }
        if (isset($data['email'])) {
            $data['email'] = Str::lower($data['email']);
        }

        $user->update($data);

        return response()->json(['user' => Storefront::user($user)]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);

        $data = $request->validate([
            'current' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'max:255', 'different:current'],
        ], [
            'current.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.min' => 'New password must be at least 6 characters.',
            'password.different' => 'New password must be different from your current password.',
        ]);

        if (! Hash::check($data['current'], $user->password)) {
            throw ValidationException::withMessages(['current' => 'Your current password is not correct.']);
        }

        $user->update(['password' => $data['password']]);

        return response()->json(['ok' => true]);
    }

    /**
     * Place an order. Prices and stock come from the database, never from the browser.
     * Only signed-in accounts may order.
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);

        $data = $request->validate([
            'customerName' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'min:8', 'max:30', 'regex:/^[+0-9\s\-()]+$/'],
            'address' => ['required', 'string', 'min:5', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'paymentMethod' => ['required', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'string', 'max:64'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'customerName.required' => 'Recipient name is required.',
            'customerName.min' => 'Recipient name must be at least 2 characters.',
            'phone.required' => 'Contact phone number is required.',
            'phone.min' => 'Phone number must be at least 8 digits.',
            'phone.regex' => 'Please enter a valid phone number.',
            'address.required' => 'Delivery address is required.',
            'address.min' => 'Please enter a full delivery address in Phnom Penh (at least 5 characters).',
            'paymentMethod.required' => 'Please select a payment method.',
            'items.required' => 'Your shopping bag is empty.',
            'items.min' => 'Please add at least one item to your bag before checking out.',
        ]);

        // Built-in checkout options send short names (khqr, cod...); methods an admin created send their code.
        $code = Storefront::PAYMENT_CODES[$data['paymentMethod']] ?? $data['paymentMethod'];
        $method = PaymentMethod::where('code', $code)->first();
        if (! $method && ! isset(Storefront::PAYMENT_LABELS[$code])) {
            throw ValidationException::withMessages(['paymentMethod' => 'Please choose one of the payment methods shown.']);
        }
        if ($method && ! $method->is_active) {
            throw ValidationException::withMessages(['paymentMethod' => 'This payment method is currently turned off.']);
        }

        $order = DB::transaction(function () use ($data, $code, $user) {
            $subtotal = 0;
            $lines = [];

            foreach ($data['items'] as $line) {
                $product = Product::where('sku', $line['id'])
                    ->orWhere('id', ctype_digit($line['id']) ? (int) $line['id'] : 0)
                    ->lockForUpdate()
                    ->first();

                if (! $product || $product->status !== 'active') {
                    throw ValidationException::withMessages(['items' => 'One of the items in your bag is no longer available.']);
                }
                if ($product->stock < $line['quantity']) {
                    throw ValidationException::withMessages(['items' => "Only {$product->stock} left of \"{$product->title}\"."]);
                }

                $product->decrement('stock', $line['quantity']);
                $lineTotal = round((float) $product->price_usd * $line['quantity'], 2);
                $subtotal += $lineTotal;
                $lines[] = [
                    'product_id' => $product->sku ?: (string) $product->id,
                    'product_title' => $product->title,
                    'product_image' => $product->image,
                    'price_usd' => $product->price_usd,
                    'price_khr' => $product->price_khr,
                    'quantity' => $line['quantity'],
                    'total_usd' => $lineTotal,
                    'total_khr' => (int) $product->price_khr * $line['quantity'],
                ];
            }

            $delivery = $subtotal >= 15 ? 0 : 1.50;
            $total = round($subtotal + $delivery, 2);

            $order = Order::create([
                'order_number' => $this->newOrderNumber(),
                'user_id' => $user->id,
                'customer_name' => $data['customerName'],
                'customer_phone' => $data['phone'],
                'delivery_address' => $data['address'],
                'delivery_notes' => $data['notes'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'subtotal_usd' => $subtotal,
                'subtotal_khr' => (int) round($subtotal * Storefront::EXCHANGE_RATE),
                'delivery_fee_usd' => $delivery,
                'delivery_fee_khr' => (int) round($delivery * Storefront::EXCHANGE_RATE),
                'total_usd' => $total,
                'total_khr' => (int) round($total * Storefront::EXCHANGE_RATE),
                'payment_method' => $code,
                'payment_status' => 'pending',
                'order_status' => 'pending_payment',
            ]);
            $order->items()->createMany($lines);

            return $order;
        });

        Storefront::forgetCatalog(); // stock changed

        Notifier::toStaff($order, 'order_placed', "New order {$order->order_number}",
            sprintf('%s ordered %d item(s), $%s by %s.', $order->customer_name, $order->items->sum('quantity'), number_format((float) $order->total_usd, 2), Storefront::paymentName($order->payment_method)));

        return response()->json(['order' => Storefront::order($order->load('items'))], 201);
    }

    /**
     * Buyer submits a payment slip (optional image) for their order.
     */
    public function uploadSlip(Request $request, string $orderNumber): JsonResponse
    {
        $order = $this->ownOrder($request, $orderNumber);

        $data = $request->validate([
            'slip' => ['nullable', 'string', 'max:3000000', Storefront::imageRule()],
        ]);

        $order->update([
            'payment_slip_url' => $data['slip'] ?? $order->payment_slip_url ?? 'submitted-without-image',
            'payment_status' => 'slip_uploaded',
        ]);

        Notifier::toStaff($order, 'slip_uploaded', "Payment slip for {$order->order_number}",
            "{$order->customer_name} uploaded a slip for $".number_format((float) $order->total_usd, 2).'. Check it and approve.');

        return response()->json(['order' => Storefront::order($order->load('items'))]);
    }

    /**
     * The checkout's simulated card / ACLEDA flows mark an order as paid (demo).
     * Staff still verify it before it ships.
     */
    public function markDemoPaid(Request $request, string $orderNumber): JsonResponse
    {
        $order = $this->ownOrder($request, $orderNumber);

        if (! in_array($order->payment_method, ['visa_card', 'acleda_khqr'], true) || $order->payment_status !== 'pending') {
            abort(422, 'This order cannot be marked as paid.');
        }

        $order->update(['payment_status' => 'paid_demo']);

        return response()->json(['order' => Storefront::order($order->load('items'))]);
    }

    /**
     * Send a customer support or merchant message.
     */
    public function sendChatMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'conversation_id' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ], [
            'conversation_id.required' => 'Conversation channel is required.',
            'message.required' => 'Message text cannot be empty.',
            'message.min' => 'Message text cannot be empty.',
            'message.max' => 'Message text cannot exceed 2,000 characters.',
        ]);

        $user = $request->user();
        $text = trim($data['message']);

        return response()->json([
            'ok' => true,
            'message' => [
                'id' => 'msg-' . (int)(microtime(true) * 1000),
                'conversation_id' => $data['conversation_id'],
                'sender' => $user?->name ?? 'Guest Buyer',
                'sender_id' => $user?->id ?? null,
                'is_me' => true,
                'text' => $text,
                'created_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Retrieve chat channels and active messages.
     */
    public function getChatMessages(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'conversations' => [
                [
                    'id' => 'support',
                    'name' => 'PsaOnline Care & Support',
                    'subtitle' => 'Orders, KHQR & Dispatch',
                    'status' => 'Online',
                    'verified' => true,
                ],
            ],
        ]);
    }

    /* ---------- Messages about an order (customer <-> shop) ---------- */

    public function orderMessages(Request $request, string $orderNumber): JsonResponse
    {
        $order = $this->ownOrder($request, $orderNumber);
        $this->markThreadRead($request, $order);

        return response()->json(['messages' => $this->messageList($order)]);
    }

    public function sendOrderMessage(Request $request, string $orderNumber): JsonResponse
    {
        $user = $this->requireUser($request);
        $order = $this->ownOrder($request, $orderNumber);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'body.required' => 'Write a message first.',
            'body.max' => 'Messages can be up to 2,000 characters.',
        ]);

        $fromStaff = $user->isStaff() && $order->user_id !== $user->id;
        $body = trim($data['body']);

        $order->messages()->create([
            'user_id' => $user->id,
            'from_staff' => $fromStaff,
            'body' => $body,
        ]);
        $this->markThreadRead($request, $order);

        $preview = mb_strimwidth($body, 0, 140, '…');
        if ($fromStaff) {
            Notifier::toCustomer($order, 'message', "New message about order {$order->order_number}", $preview,
                'order-detail.html?order='.rawurlencode($order->order_number).'#messages');
        } else {
            Notifier::toStaff($order, 'message', "{$order->customer_name} sent a message ({$order->order_number})", $preview, $user->id);
        }

        return response()->json(['messages' => $this->messageList($order)], 201);
    }

    /** Opening a thread marks the other side's messages, and this order's message notices, as read. */
    private function markThreadRead(Request $request, Order $order): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }
        $viewerIsStaff = $user->isStaff() && $order->user_id !== $user->id;
        $order->messages()->where('from_staff', ! $viewerIsStaff)->whereNull('read_at')->update(['read_at' => now()]);
        \App\Models\UserNotification::where('user_id', $user->id)->where('order_id', $order->id)
            ->where('type', 'message')->whereNull('read_at')->update(['read_at' => now()]);
    }

    private function messageList(Order $order): array
    {
        return $order->messages()->with('user:id,name')->orderBy('created_at')->orderBy('id')->get()
            ->map(fn (OrderMessage $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'fromStaff' => $m->from_staff,
                'author' => $m->from_staff ? 'PsaOnline' : ($m->user?->name ?? $order->customer_name),
                'staffName' => $m->from_staff ? $m->user?->name : null,
                'createdAt' => $m->created_at?->toIso8601String(),
            ])->all();
    }

    /* ---------- Reviews ---------- */

    /** The customer reviews items from one of their delivered orders. */
    public function reviewOrder(Request $request, string $orderNumber): JsonResponse
    {
        $user = $this->requireUser($request);
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        abort_unless($order->user_id === $user->id, 403, 'You can only review your own orders.');
        if ($order->order_status !== 'delivered') {
            throw ValidationException::withMessages(['reviews' => 'You can review items once your order has been delivered.']);
        }

        $data = $request->validate([
            'reviews' => ['required', 'array', 'min:1', 'max:50'],
            'reviews.*.id' => ['required', 'string', 'max:64'],
            'reviews.*.rating' => ['required', 'integer', 'between:1,5'],
            'reviews.*.comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'reviews.*.rating.required' => 'Choose a star rating for each item.',
            'reviews.*.rating.between' => 'Ratings go from 1 to 5 stars.',
        ]);

        $skus = $order->items->pluck('product_id')->all();
        foreach ($data['reviews'] as $review) {
            if (! in_array($review['id'], $skus, true)) {
                throw ValidationException::withMessages(['reviews' => 'One of those items is not in this order.']);
            }
        }

        DB::transaction(function () use ($data, $order, $user) {
            foreach ($data['reviews'] as $review) {
                ProductReview::updateOrCreate(
                    ['order_id' => $order->id, 'product_sku' => $review['id']],
                    ['user_id' => $user->id, 'rating' => $review['rating'], 'comment' => trim($review['comment'] ?? '') ?: null],
                );
                $this->refreshProductRating($review['id']);
            }
        });

        Storefront::forgetCatalog();

        return response()->json(['order' => Storefront::order($order->refresh()->load('items'))]);
    }

    /** Public list of reviews for a product page. */
    public function productReviews(string $sku): JsonResponse
    {
        $query = ProductReview::where('product_sku', $sku);
        $count = (clone $query)->count();

        return response()->json([
            'average' => $count ? round((float) (clone $query)->avg('rating'), 1) : null,
            'count' => $count,
            'reviews' => (clone $query)->with('user:id,name')->latest()->limit(30)->get()
                ->map(fn (ProductReview $r) => [
                    'rating' => $r->rating,
                    'comment' => $r->comment,
                    'author' => $this->shortName($r->user?->name),
                    'date' => $r->created_at?->timezone(config('app.timezone'))->format('d M Y'),
                ])->all(),
        ]);
    }

    private function refreshProductRating(string $sku): void
    {
        $reviews = ProductReview::where('product_sku', $sku);
        Product::where('sku', $sku)->update([
            'rating' => round((float) (clone $reviews)->avg('rating'), 1),
            'review_count' => (clone $reviews)->count(),
        ]);
    }

    /** "Sophea Chhum" becomes "Sophea C." so reviews don't publish full names. */
    private function shortName(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        if (! $parts || $parts[0] === '') {
            return 'Customer';
        }

        return count($parts) > 1 ? $parts[0].' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : $parts[0];
    }

    private function ownOrder(Request $request, string $orderNumber): Order
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $user = $request->user();
        $isOwner = ($user && $order->user_id === $user->id)
            || in_array($orderNumber, $request->session()->get('guest_orders', []), true);

        abort_unless($isOwner || $user?->isStaff(), 403, 'This is not your order.');

        return $order;
    }

    private function newOrderNumber(): string
    {
        do {
            $number = 'PSA-'.Str::upper(Str::random(8));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    private function requireUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user, 401, 'Please log in first.');

        return $user;
    }

    private function endSession(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
