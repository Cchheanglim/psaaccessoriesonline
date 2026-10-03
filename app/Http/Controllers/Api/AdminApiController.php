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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin portal API. Staff may run fulfilment and edit products;
 * only admins may delete products, cancel orders, manage users or payment methods.
 */
class AdminApiController extends Controller
{
    /* ---------- Products ---------- */

    public function storeProduct(Request $request): JsonResponse
    {
        $this->requireRole($request, 'staff');
        $data = $this->validateProduct($request);

        $product = Product::create($data + ['sku' => $this->newSku()]);

        Storefront::forgetCatalog();

        return response()->json(['product' => Storefront::product($product)], 201);
    }

    public function updateProduct(Request $request, string $sku): JsonResponse
    {
        $this->requireRole($request, 'staff');
        $product = $this->findProduct($sku);

        $product->update($this->validateProduct($request, $product));

        Storefront::forgetCatalog();

        return response()->json(['product' => Storefront::product($product)]);
    }

    public function destroyProduct(Request $request, string $sku): JsonResponse
    {
        $this->requireRole($request, 'admin');
        $this->findProduct($sku)->delete();
        Storefront::forgetCatalog();

        return response()->json(['ok' => true]);
    }

    /* ---------- Orders ---------- */

    public function updateOrder(Request $request, string $orderNumber): JsonResponse
    {
        $user = $this->requireRole($request, 'staff');
        $data = $request->validate([
            'action' => ['required', Rule::in(['verify', 'reject', 'dispatch', 'deliver', 'cancel'])],
        ]);

        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        if ($data['action'] === 'cancel' && ! $user->isAdmin()) {
            abort(403, 'Only an Admin can cancel orders.');
        }
        if ($order->order_status === 'cancelled') {
            abort(422, 'This order was already cancelled.');
        }

        DB::transaction(function () use ($order, $data) {
            match ($data['action']) {
                'verify' => $order->update(['payment_status' => 'verified', 'order_status' => 'processing', 'paid_at' => now()]),
                'reject' => $order->update(['payment_status' => 'failed']),
                'dispatch' => $order->update(['order_status' => 'out_for_delivery']),
                'deliver' => $order->update(array_filter([
                    'order_status' => 'delivered',
                    'payment_status' => $order->payment_method === 'cod' ? 'verified' : null,
                    'paid_at' => $order->paid_at ?? now(),
                ])),
                'cancel' => $this->cancelOrder($order),
            };
        });

        Storefront::forgetCatalog();

        return response()->json(['order' => Storefront::order($order->refresh()->load('items'))]);
    }

    private function cancelOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            Product::where('sku', $item->product_id)->increment('stock', $item->quantity);
        }
        $order->update(['order_status' => 'cancelled']);
    }

    /* ---------- Users ---------- */

    public function storeUser(Request $request): JsonResponse
    {
        $this->requireRole($request, 'admin');
        $data = $this->validateUser($request);

        $user = User::create($data);

        return response()->json(['user' => Storefront::user($user)], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $admin = $this->requireRole($request, 'admin');
        $data = $this->validateUser($request, $user);

        if ($user->is($admin) && (($data['role'] ?? 'admin') !== 'admin' || ($data['status'] ?? 'Active') !== 'Active')) {
            throw ValidationException::withMessages(['role' => 'You cannot remove your own admin access.']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json(['user' => Storefront::user($user)]);
    }

    /* ---------- Payment methods ---------- */

    public function storePaymentMethod(Request $request): JsonResponse
    {
        $this->requireRole($request, 'admin');
        $data = $this->validatePaymentMethod($request);
        $data['code'] = $this->uniqueCode($data['name']);

        $method = PaymentMethod::create($data);

        Storefront::forgetCatalog();

        return response()->json(['paymentMethod' => Storefront::paymentMethod($method)], 201);
    }

    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $this->requireRole($request, 'admin');
        $paymentMethod->update($this->validatePaymentMethod($request, partial: true));

        Storefront::forgetCatalog();

        return response()->json(['paymentMethod' => Storefront::paymentMethod($paymentMethod)]);
    }

    public function destroyPaymentMethod(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $this->requireRole($request, 'admin');
        if (in_array($paymentMethod->code, Storefront::PAYMENT_CODES, true)) {
            abort(422, 'Built-in checkout methods cannot be deleted. Turn it off instead.');
        }
        $paymentMethod->delete();
        Storefront::forgetCatalog();

        return response()->json(['ok' => true]);
    }

    /* ---------- Helpers ---------- */

    private function requireRole(Request $request, string $role): User
    {
        $user = $request->user();
        abort_unless($user, 401, 'Please log in first.');
        abort_unless($role === 'admin' ? $user->isAdmin() : $user->isStaff(), 403, 'Your role does not allow this.');

        return $user;
    }

    private function findProduct(string $sku): Product
    {
        return Product::where('sku', $sku)
            ->orWhere('id', str_starts_with($sku, 'db-') ? (int) substr($sku, 3) : 0)
            ->firstOrFail();
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $partial = $product !== null;
        $req = $partial ? 'sometimes' : 'required';

        $data = $request->validate([
            'title' => [$req, 'string', 'min:2', 'max:255'],
            'titleKhmer' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => [$req, 'string', 'max:50'],
            'categoryLabel' => ['sometimes', 'nullable', 'string', 'max:100'],
            'priceUSD' => [$req, 'numeric', 'min:0.01', 'max:100000'],
            'stock' => [$req, 'integer', 'min:0', 'max:1000000'],
            'badge' => ['sometimes', 'nullable', 'string', 'max:60'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::in(['active', 'draft', 'archived'])],
            'gallery' => ['sometimes', 'array', 'max:8'],
            'gallery.*' => ['string', 'max:3000000', Storefront::imageRule()],
            'specifications' => ['sometimes', 'array', 'max:30'],
            'specifications.*' => ['nullable', 'string', 'max:255'],
        ], [
            'title.required' => 'Product title is required.',
            'title.min' => 'Product title must be at least 2 characters.',
            'category.required' => 'Product category is required.',
            'priceUSD.required' => 'Price in USD is required.',
            'priceUSD.min' => 'Price must be greater than $0.00.',
            'stock.required' => 'Stock quantity is required.',
            'stock.min' => 'Stock quantity cannot be negative.',
        ]);

        $map = [
            'title' => 'title', 'titleKhmer' => 'title_khmer', 'category' => 'category',
            'categoryLabel' => 'category_label', 'stock' => 'stock', 'badge' => 'badge',
            'description' => 'description', 'status' => 'status', 'specifications' => 'specifications',
        ];
        $out = [];
        foreach ($map as $in => $column) {
            if (array_key_exists($in, $data)) {
                $out[$column] = $data[$in];
            }
        }
        if (isset($data['title'])) {
            $out['slug'] = $this->uniqueSlug($data['title'], $product?->id);
        }
        if (isset($data['priceUSD'])) {
            $out['price_usd'] = round((float) $data['priceUSD'], 2);
            $out['price_khr'] = (int) round($data['priceUSD'] * Storefront::EXCHANGE_RATE);
        }
        if (array_key_exists('gallery', $data)) {
            $out['gallery'] = array_values($data['gallery']);
            $out['image'] = $out['gallery'][0] ?? null;
        }

        return $out;
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $req = $user ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => [$req, 'string', 'min:2', 'max:100'],
            'email' => [$req, 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'min:8', 'max:30', 'regex:/^[+0-9\s\-()]+$/'],
            'role' => [$req, Rule::in(['Admin', 'Staff', 'Buyer'])],
            'status' => ['sometimes', Rule::in(['Active', 'Suspended'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'max:255'],
        ], [
            'name.required' => 'User full name is required.',
            'name.min' => 'User name must be at least 2 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already assigned to another user.',
            'phone.regex' => 'Please enter a valid phone number.',
            'role.required' => 'Please select a role for this user.',
            'password.required' => 'Password is required for new accounts.',
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        if (isset($data['role'])) {
            $data['role'] = Str::lower($data['role']);
        }
        if (isset($data['email'])) {
            $data['email'] = Str::lower($data['email']);
        }

        return $data;
    }

    private function validatePaymentMethod(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => [$req, 'string', 'min:2', 'max:100'],
            'type' => ['sometimes', Rule::in(['khqr', 'bank', 'cod', 'other'])],
            'accountName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'accountNumber' => ['sometimes', 'nullable', 'string', 'max:50'],
            'qrData' => ['sometimes', 'nullable', 'string', 'max:3000000'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'isActive' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Payment gateway name is required.',
            'name.min' => 'Name must be at least 2 characters.',
        ]);

        $map = ['name' => 'name', 'type' => 'type', 'accountName' => 'account_name', 'accountNumber' => 'account_number',
            'qrData' => 'qr_data', 'description' => 'description', 'isActive' => 'is_active'];
        $out = [];
        foreach ($map as $in => $column) {
            if (array_key_exists($in, $data)) {
                $out[$column] = $data[$in];
            }
        }

        return $out;
    }

    private function newSku(): string
    {
        do {
            $sku = 'psa-'.Str::lower(Str::random(6));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    private function uniqueSlug(string $title, ?int $ignoreId): string
    {
        $base = Str::slug($title) ?: 'product';
        $slug = $base;
        $n = 2;
        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'method';
        $code = $base;
        $n = 2;
        while (PaymentMethod::where('code', $code)->exists()) {
            $code = $base.'_'.$n++;
        }

        return $code;
    }
}
