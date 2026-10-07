<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\PromoCode;
use App\Models\Role;
use App\Models\User;
use App\Support\Inventory;
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

        $orders = (! $isStaff && ! $user && ! $guestOrders) ? collect() : Order::with(Order::PAGE_RELATIONS)
            ->withCount(['messages', 'reviews', 'messages as unread_replies_count' => fn ($q) => $q->fromStaff()->whereNull('read_at')])
            ->when(! $isStaff, function ($q) use ($user, $guestOrders) {
                $q->where(function ($q) use ($user, $guestOrders) {
                    $q->whereIn('order_number', $guestOrders);
                    if ($user) {
                        $q->orWhere(fn ($q) => $q->ofUser($user->id));
                    }
                });
            })
            ->latest()
            ->get();

        return response()->json([
            'csrf' => csrf_token(),
            'user' => $user ? Storefront::user($user) : null,
            'products' => $catalog['products'],
            'categories' => $catalog['categories'],
            'showcase' => $catalog['showcase'],
            'sortOptions' => $catalog['sortOptions'],
            'settings' => $catalog['settings'],
            'paymentMethods' => $catalog['paymentMethods'],
            'orders' => $orders->map(fn ($o) => Storefront::order($o))->values(),
            'users' => $user?->isStaff() && $user->hasPermission('manage_users')
                ? User::with(['defaultAddress', 'roleRecord'])->orderBy('id')->get()->map(fn ($u) => Storefront::user($u))->values()
                : [],
            'coupons' => Storefront::coupons(),
            'roles' => $user?->isStaff() && $user->hasPermission('manage_users')
                ? Role::orderBy('id')->get()->map(fn (Role $r) => ['name' => Role::label($r->name), 'description' => $r->description, 'portal' => $r->name !== 'buyer'])->values()
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
            'phone' => ['required', 'string', 'min:8', 'max:30', 'regex:/^[+0-9\s\-()]+$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ], [
            'phone.unique' => 'This phone number is already registered.',
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
            'phone' => ['sometimes', 'nullable', 'string', 'min:8', 'max:30', 'regex:/^[+0-9\s\-()]+$/', Rule::unique('users', 'phone')->ignore($user->id)],
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
            'phone.unique' => 'This phone number is already used by another account.',
            'address.min' => 'Delivery address must be at least 5 characters.',
        ]);

        if (array_key_exists('photo', $data)) {
            $data['avatar_url'] = $data['photo'];
            unset($data['photo']);
        }
        if (array_key_exists('banner', $data)) {
            $data['banner_url'] = $data['banner'];
            unset($data['banner']);
        }
        if (isset($data['email'])) {
            $data['email'] = Str::lower($data['email']);
        }
        if (array_key_exists('address', $data)) {
            $this->saveDefaultAddress($user, $data['address']);
            unset($data['address']);
        }

        $user->update($data);
        $user->unsetRelation('defaultAddress');

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
     * Only signed-in customer accounts may order (staff and admins run the shop).
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);
        // Staff and admin accounts run the shop; buying is for customer accounts, so sales,
        // stock and memberships only ever come from real customers.
        abort_if($user->isStaff(), 403, 'Staff and admin accounts can\'t place orders. Sign in with a customer account to shop.');

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
            'promoCode' => ['nullable', 'string', 'max:30'],
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

        $method ??= PaymentMethod::create(['code' => $code, 'name' => Storefront::PAYMENT_LABELS[$code], 'type' => 'other', 'is_active' => true]);
        $customer = $user;

        $order = DB::transaction(function () use ($data, $method, $user, $customer) {
            // Merge repeated lines for the same product, then check every product before writing anything.
            $wanted = [];
            foreach ($data['items'] as $line) {
                $product = Product::findByKey($line['id']);
                if (! $product || $product->status !== 'active') {
                    throw ValidationException::withMessages(['items' => 'One of the items in your bag is no longer available.']);
                }
                $wanted[$product->id] = ['product' => $product, 'quantity' => ($wanted[$product->id]['quantity'] ?? 0) + $line['quantity']];
            }

            $subtotal = 0;
            foreach ($wanted as $line) {
                $onHand = Inventory::lock($line['product']);
                if ($onHand < $line['quantity']) {
                    throw ValidationException::withMessages(['items' => "Only {$onHand} left of \"{$line['product']->title}\"."]);
                }
                $subtotal += round($line['product']->sellingPrice() * $line['quantity'], 2);
            }

            // Pro and Max members get their tier's discount on the items, then a promo code
            // takes money off what is left (checked again here, the browser only previews it).
            $memberDiscount = $customer->tier->discountFor($subtotal);
            $promo = null;
            $discount = 0.0;
            if (filled($data['promoCode'] ?? null)) {
                $promo = PromoCode::where('code', PromoCode::normalize($data['promoCode']))->lockForUpdate()->first()
                    ?? throw ValidationException::withMessages(['promoCode' => 'We could not find code "'.PromoCode::normalize($data['promoCode']).'". Check the spelling.']);
                if ($problem = $promo->problemFor($customer, $subtotal)) {
                    throw ValidationException::withMessages(['promoCode' => $problem]);
                }
                $discount = $promo->discountFor($subtotal - $memberDiscount);
            }

            $freeItem = Product::whereIn('id', array_keys($wanted))->whereHas('freeDeliveryGroups')->exists();
            $delivery = Storefront::deliveryFee($subtotal, $freeItem); // worked out on the items before any discount
            $total = round($subtotal - $memberDiscount - $discount + $delivery, 2);

            // The order points at the address it ships to (reused when the same one was used before).
            $address = $this->addressForOrder($customer, $data);

            $order = Order::create([
                'order_number' => $this->newOrderNumber(),
                'user_id' => $user->id,
                'address_id' => $address->id,
                'delivery_notes' => $data['notes'] ?? null,
                'promo_code_id' => $promo?->id,
                'member_discount_usd' => $memberDiscount,
                'delivery_fee_usd' => $delivery,
            ]);
            $order->moveTo('pending_payment', $user, 'Order placed');
            $order->payments()->create(['payment_method_id' => $method->id, 'amount_usd' => $total, 'status' => 'pending']);

            foreach ($wanted as $line) {
                $product = $line['product'];
                $item = $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $line['quantity'],
                    'unit_price_usd' => $product->sellingPrice(), // the price agreed in this sale (after any product sale)
                    'unit_cost_usd' => $product->averageCost(), // what it cost the shop at this time
                ]);
                Inventory::move($product, 'sale', -$line['quantity'], [
                    'by' => $user, 'order_item_id' => $item->id, 'reason' => "Sold in order {$order->order_number}",
                ]);
            }

            return $order;
        });

        Storefront::forgetCatalog(); // stock changed


        return response()->json(['order' => Storefront::order($order->fresh())], 201);
    }

    /**
     * Checkout preview: does this code work for these items, and how much does it take off?
     * placeOrder checks it again, so this only tells the customer early.
     */
    public function checkPromoCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'string', 'max:64'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ], ['code.required' => 'Type a promo code first.']);

        $promo = PromoCode::findByCode($data['code']);
        if (! $promo) {
            throw ValidationException::withMessages(['code' => 'We could not find code "'.PromoCode::normalize($data['code']).'". Check the spelling.']);
        }
        $subtotal = collect($data['items'])->sum(function ($line) {
            $product = Product::findByKey($line['id']);

            return $product && $product->status === 'active' ? round($product->sellingPrice() * $line['quantity'], 2) : 0;
        });
        $customer = $request->user();
        if ($problem = $promo->problemFor($customer, (float) $subtotal)) {
            throw ValidationException::withMessages(['code' => $problem]);
        }
        $memberDiscount = $customer ? $customer->tier->discountFor((float) $subtotal) : 0.0;

        return response()->json([
            'code' => $promo->code,
            'label' => $promo->label(),
            'description' => $promo->description,
            'memberDiscountUSD' => number_format($memberDiscount, 2, '.', ''),
            'discountUSD' => number_format($promo->discountFor((float) $subtotal - $memberDiscount), 2, '.', ''),
        ]);
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

        abort_unless($order->order_status === 'pending_payment', 422, 'This order is no longer waiting for payment.');
        $payment = $order->latestPayment;

        if (! $payment || in_array($payment->status, ['failed', 'refunded'], true)) {
            // A rejected slip stays on record; the new slip is a new payment attempt.
            $order->payments()->create([
                'payment_method_id' => $order->payment_method_id, 'amount_usd' => $order->total_usd,
                'status' => 'slip_uploaded', 'slip_url' => $data['slip'] ?? null,
            ]);
        } else {
            $payment->update(['status' => 'slip_uploaded', 'slip_url' => $data['slip'] ?? $payment->slip_url]);
        }
        $order->unsetRelation('latestPayment');


        return response()->json(['order' => Storefront::order($order->fresh())]);
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

        $order->latestPayment->update(['status' => 'paid_demo']);

        return response()->json(['order' => Storefront::order($order->fresh())]);
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

        return response()->json($this->thread($order, $request->user()));
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

        $fromStaff = $order->user_id !== $user->id;
        abort_if($fromStaff && ! $user->hasPermission('manage_messages'), 403, 'Your role does not have the "Customer messages" permission.');
        $body = trim($data['body']);

        $order->messages()->create([
            'sender_id' => $user->id,
            'body' => $body,
        ]);
        $this->markThreadRead($request, $order);


        return response()->json($this->thread($order, $user), 201);
    }

    /**
     * Delete one message. Customers can delete their own messages; staff with "Customer messages"
     * can delete any. The row stays with deleted_at and deleted_by; both sides see "Message deleted by ...".
     */
    public function destroyOrderMessage(Request $request, string $orderNumber, int $message): JsonResponse
    {
        $user = $this->requireUser($request);
        $order = $this->ownOrder($request, $orderNumber);
        $row = $order->messages()->whereKey($message)->firstOrFail();

        abort_unless($this->canDeleteMessage($user, $order, $row), 403, 'You can only delete your own messages.');
        $order->messages()->whereKey($row->id)->update(['deleted_by' => $user->id, 'deleted_at' => now()]);

        return response()->json($this->thread($order, $user));
    }

    /** Delete the whole chat of an order (staff with "Customer messages" only). */
    public function destroyOrderMessages(Request $request, string $orderNumber): JsonResponse
    {
        $user = $this->requireUser($request);
        $order = $this->ownOrder($request, $orderNumber);
        abort_unless($user->isStaff() && $user->hasPermission('manage_messages'), 403, 'Only staff can delete a whole chat.');
        $order->messages()->update(['deleted_by' => $user->id, 'deleted_at' => now()]);

        return response()->json($this->thread($order, $user));
    }

    private function canDeleteMessage(?User $user, Order $order, OrderMessage $message): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isStaff() && $user->hasPermission('manage_messages')) {
            return true;
        }

        return $message->sender_id === $user->id && ! $message->isFromStaff($order);
    }

    /** The chat and what the viewer may delete in it. */
    private function thread(Order $order, ?User $viewer): array
    {
        return [
            'messages' => $this->messageList($order, $viewer),
            'canDeleteChat' => (bool) ($viewer?->isStaff() && $viewer->hasPermission('manage_messages')),
        ];
    }

    /** Opening a thread marks the other side's messages as read. */
    private function markThreadRead(Request $request, Order $order): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }
        // The viewer reads the other side's messages: the customer reads staff replies, staff read the customer's.
        $viewerIsCustomer = $order->user_id === $user->id;
        OrderMessage::where('order_id', $order->id)->whereNull('read_at')
            ->when($viewerIsCustomer, fn ($q) => $q->where(fn ($q) => $q->where('sender_id', '!=', $user->id)->orWhereNull('sender_id')),
                fn ($q) => $q->where('sender_id', $order->user_id))
            ->update(['read_at' => now()]);
    }

    /**
     * The chat, oldest first. A deleted message has no text, only who deleted it and when; several
     * deleted in a row by the same person (a whole chat) show as one line with a count.
     */
    private function messageList(Order $order, ?User $viewer = null): array
    {
        $viewerIsStaff = $viewer && $viewer->id !== $order->user_id;
        $list = [];
        $order->messages()->withTrashed()->with(['sender:id,name', 'deleter:id,name'])->orderBy('created_at')->orderBy('id')->get()
            ->each(function (OrderMessage $m) use ($order, $viewer, $viewerIsStaff, &$list) {
                $fromStaff = $m->isFromStaff($order);
                $row = [
                    'id' => $m->id,
                    'body' => $m->trashed() ? null : $m->body,
                    'fromStaff' => $fromStaff,
                    'author' => $fromStaff ? 'PsaOnline' : ($m->sender?->name ?? $order->customer_name),
                    'staffName' => $fromStaff ? $m->sender?->name : null,
                    'createdAt' => $m->created_at?->toIso8601String(),
                    'canDelete' => ! $m->trashed() && $this->canDeleteMessage($viewer, $order, $m),
                ];
                if ($m->trashed()) {
                    // Empty deleted_by = the automatic clean-up. The customer sees "PsaOnline" for staff;
                    // staff see the staff member's name.
                    $byCustomer = $m->deleted_by !== null && $m->deleted_by === $order->user_id;
                    $row += [
                        'deleted' => true,
                        'deletedAt' => $m->deleted_at?->toIso8601String(),
                        'deletedBy' => $m->deleted_by === null ? null
                            : ($byCustomer ? ($m->deleter?->name ?? 'the customer') : ($viewerIsStaff ? ($m->deleter?->name ?? 'PsaOnline') : 'PsaOnline')),
                        'deletedByMe' => $viewer && $m->deleted_by === $viewer->id,
                        'count' => 1,
                    ];
                    $last = count($list) - 1;
                    if ($last >= 0 && ! empty($list[$last]['deleted']) && $list[$last]['deletedBy'] === $row['deletedBy']) {
                        $list[$last]['count']++;

                        return;
                    }
                }
                $list[] = $row;
            });

        return $list;
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

        $order->loadMissing('items.product:id,sku');
        $productIds = []; // storefront id (SKU) => product id, for this order's items
        foreach ($order->items as $item) {
            if ($item->product_id) {
                $productIds[$item->product?->sku ?? 'db-'.$item->product_id] = $item->product_id;
            }
        }
        foreach ($data['reviews'] as $review) {
            if (! isset($productIds[$review['id']])) {
                throw ValidationException::withMessages(['reviews' => 'One of those items is not in this order.']);
            }
        }

        DB::transaction(function () use ($data, $order, $productIds) {
            foreach ($data['reviews'] as $review) {
                ProductReview::updateOrCreate(
                    ['order_id' => $order->id, 'product_id' => $productIds[$review['id']]],
                    ['customer_id' => $order->customer_id, 'rating' => $review['rating'], 'comment' => trim($review['comment'] ?? '') ?: null],
                );
            }
        });

        Storefront::forgetCatalog();

        return response()->json(['order' => Storefront::order($order->fresh())]);
    }

    /** Public list of reviews for a product page. */
    public function productReviews(string $sku): JsonResponse
    {
        $product = Product::findByKey($sku);
        $query = ProductReview::where('product_id', $product?->id ?? 0);
        $count = (clone $query)->count();

        return response()->json([
            'average' => $count ? round((float) (clone $query)->avg('rating'), 1) : null,
            'count' => $count,
            'reviews' => (clone $query)->with('customer.user:id,name')->latest()->limit(30)->get()
                ->map(fn (ProductReview $r) => [
                    'rating' => $r->rating,
                    'comment' => $r->comment,
                    'author' => $this->shortName($r->customer?->user?->name),
                    'date' => $r->created_at?->timezone(config('app.timezone'))->format('d M Y'),
                ])->all(),
        ]);
    }

    /** The profile's delivery address is the customer's default saved address. */
    private function saveDefaultAddress(User $user, ?string $line): void
    {
        $address = $user->defaultAddress;

        if (trim((string) $line) === '') {
            if ($address) {
                $this->retireAddress($address);
            }

            return;
        }
        if ($address && ! $address->orders()->exists()) {
            $address->update(['address_line' => $line]);

            return;
        }
        // An address an order shipped to never changes: save a new one and archive the old.
        if ($address) {
            $this->retireAddress($address);
        }
        $user->asCustomer()->addresses()->create([
            'label' => $address?->label ?? 'Home', 'recipient_name' => $address?->recipient_name ?? $user->name,
            'phone' => $address?->phone ?? $user->phone, 'address_line' => $line, 'is_default' => true,
        ]);
    }

    /** Remove an address from the address book: archive it if an order used it, otherwise delete it. */
    private function retireAddress(CustomerAddress $address): void
    {
        if ($address->orders()->exists()) {
            $address->update(['is_default' => false, 'archived_at' => now()]);
        } else {
            $address->delete();
        }
    }

    /**
     * The address an order ships to: the customer's own address with exactly these details if there is
     * one, otherwise a new one (it becomes the default when the customer has none yet).
     */
    private function addressForOrder(User $customer, array $data): CustomerAddress
    {
        $details = [
            'recipient_name' => trim($data['customerName']),
            'phone' => trim($data['phone']),
            'address_line' => trim($data['address']),
            'latitude' => isset($data['latitude']) ? round((float) $data['latitude'], 7) : null,
            'longitude' => isset($data['longitude']) ? round((float) $data['longitude'], 7) : null,
        ];
        $same = $customer->addresses()->whereNull('archived_at')->get()->first(fn (CustomerAddress $a) => $a->recipient_name === $details['recipient_name']
            && $a->phone === $details['phone'] && $a->address_line === $details['address_line']
            && round((float) $a->latitude, 7) === round((float) $details['latitude'], 7)
            && round((float) $a->longitude, 7) === round((float) $details['longitude'], 7));

        return $same ?? $customer->asCustomer()->addresses()->create($details + [
            'label' => 'Delivery', 'is_default' => ! $customer->defaultAddress()->exists(),
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
        $order = Order::with(Order::PAGE_RELATIONS)->where('order_number', $orderNumber)->firstOrFail();
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
