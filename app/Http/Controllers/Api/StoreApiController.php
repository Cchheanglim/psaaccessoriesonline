<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
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

        $orders = (! $isStaff && ! $user && ! $guestOrders) ? collect() : Order::with('items')
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
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
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

        return response()->json(['user' => Storefront::user($user)]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
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

        return response()->json(['user' => Storefront::user($user)], 201);
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo' => ['sometimes', 'nullable', 'string', 'max:3000000', Storefront::imageRule()],
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
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        if (! Hash::check($data['current'], $user->password)) {
            throw ValidationException::withMessages(['current' => 'Your current password is not correct.']);
        }

        $user->update(['password' => $data['password']]);

        return response()->json(['ok' => true]);
    }

    /**
     * Place an order. Prices and stock come from the database, never from the browser.
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customerName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'paymentMethod' => ['required', Rule::in(array_keys(Storefront::PAYMENT_CODES))],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'string', 'max:64'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $code = Storefront::PAYMENT_CODES[$data['paymentMethod']];
        $method = PaymentMethod::where('code', $code)->first();
        if ($method && ! $method->is_active) {
            throw ValidationException::withMessages(['paymentMethod' => 'This payment method is currently turned off.']);
        }

        $order = DB::transaction(function () use ($data, $code, $request) {
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
                'user_id' => $request->user()?->id,
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

        if (! $request->user()) {
            $request->session()->push('guest_orders', $order->order_number);
        }

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
