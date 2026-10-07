<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\SortOption;
use App\Models\User;
use App\Support\Inventory;
use App\Support\Storefront;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Staff portal API. Every action checks one permission (see Permission::LIST); which roles
 * have it is ticked by an admin under Staff & roles. Admins have all of them.
 */
class AdminApiController extends Controller
{
    /* ---------- Products ---------- */

    public function storeProduct(Request $request): JsonResponse
    {
        $user = $this->requirePermission($request, 'manage_products');
        $data = $this->validateProduct($request);

        $product = DB::transaction(function () use ($data, $user) {
            $product = Product::create($data['product'] + ['sku' => $this->newSku(), 'status' => $data['product']['status'] ?? 'active']);
            $this->saveProductParts($product, $data, $user);

            return $product;
        });

        Storefront::forgetCatalog();

        return response()->json(['product' => Storefront::product($product->fresh())], 201);
    }

    public function updateProduct(Request $request, string $sku): JsonResponse
    {
        $user = $this->requirePermission($request, 'manage_products');
        $product = $this->findProduct($sku);
        $data = $this->validateProduct($request, $product);
        if (array_key_exists('stock', $data) && $data['stock'] !== $product->stock_on_hand) {
            $this->requirePermission($request, 'manage_stock');
        }

        DB::transaction(function () use ($product, $data, $user) {
            $product->update($data['product']);
            $this->saveProductParts($product, $data, $user);
        });

        Storefront::forgetCatalog();

        return response()->json(['product' => Storefront::product($product->fresh())]);
    }

    /** Products that were ordered or reviewed are archived, so old orders and reviews keep them. */
    public function destroyProduct(Request $request, string $sku): JsonResponse
    {
        $this->requirePermission($request, 'delete_products');
        $product = $this->findProduct($sku);
        $archived = $product->orderItems()->exists() || $product->reviews()->exists() || $product->purchaseOrderItems()->exists();

        DB::transaction(function () use ($product, $archived) {
            if ($archived) {
                $product->update(['status' => 'archived']);
            } else {
                $product->stockMovements()->delete();
                $product->delete();
            }
        });
        Storefront::forgetCatalog();

        return response()->json(['ok' => true, 'archived' => $archived]);
    }

    /** Puts several products into one category at once. */
    public function moveProducts(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_products');
        $data = $request->validate([
            'products' => ['required', 'array', 'min:1', 'max:500'],
            'products.*' => ['string', 'max:64'],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
        ], ['categoryId.required' => 'Choose the category to move them to.']);

        $products = collect($data['products'])->unique()->map(fn ($key) => Product::findByKey($key))->filter();
        abort_if($products->isEmpty(), 404, 'None of those products were found.');

        Product::whereIn('id', $products->pluck('id'))->update(['category_id' => $data['categoryId']]);
        Storefront::forgetCatalog();

        return response()->json([
            'moved' => $products->count(),
            'products' => Product::whereIn('id', $products->pluck('id'))->get()->map(fn ($p) => Storefront::product($p))->values(),
            'categories' => Storefront::categories(true),
        ]);
    }

    /* ---------- Categories ---------- */

    public function storeCategory(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_products');
        $data = $this->validateCategory($request);

        $category = Category::create([
            'name' => $data['name'],
            'parent_id' => $data['parentId'] ?? null,
            'slug' => $this->uniqueCategorySlug(Str::slug($data['name']) ?: 'category'),
            'is_active' => $data['isActive'] ?? true,
        ]);
        Storefront::forgetCatalog();

        return response()->json(['category' => Storefront::category($category), 'categories' => Storefront::categories(true)], 201);
    }

    /** Rename, move under another category (or to the top), show or hide. The slug stays, so links keep working. */
    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $this->requirePermission($request, 'manage_products');
        $data = $this->validateCategory($request, $category);

        $changes = [];
        if (array_key_exists('name', $data)) {
            $changes['name'] = $data['name'];
        }
        if (array_key_exists('parentId', $data)) {
            $changes['parent_id'] = $data['parentId'];
        }
        if (array_key_exists('isActive', $data)) {
            $changes['is_active'] = $data['isActive'];
        }
        $category->update($changes);
        Storefront::forgetCatalog();

        return response()->json(['category' => Storefront::category($category->fresh()), 'categories' => Storefront::categories(true)]);
    }

    /** Only empty categories can be deleted, so no product is ever left without one. */
    public function destroyCategory(Request $request, Category $category): JsonResponse
    {
        $this->requirePermission($request, 'delete_products');

        $products = $category->products()->count();
        if ($products > 0) {
            throw ValidationException::withMessages(['category' => "Move its {$products} product".($products === 1 ? '' : 's').' to another category first.']);
        }
        if ($category->children()->exists()) {
            throw ValidationException::withMessages(['category' => 'Delete or move its sub-categories first.']);
        }

        $category->delete();
        Storefront::forgetCatalog();

        return response()->json(['ok' => true, 'categories' => Storefront::categories(true)]);
    }

    /* ---------- Shop "Sort By" menu ---------- */

    public function storeSortOption(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_products');
        $data = $this->validateSortOption($request);

        $option = DB::transaction(function () use ($data) {
            $option = SortOption::create($data['option'] + ['sort_order' => (int) SortOption::max('sort_order') + 1]);
            if ($option->type === 'group') {
                $option->products()->sync($data['productIds'] ?? []);
            }

            return $option;
        });
        Storefront::forgetCatalog();

        return response()->json(['sortOption' => Storefront::sortOption($option->fresh()), 'sortOptions' => $this->allSortOptions()], 201);
    }

    /** Rename, change what it does, show/hide, free delivery on/off, and (groups) which products are in it. */
    public function updateSortOption(Request $request, SortOption $sortOption): JsonResponse
    {
        $this->requirePermission($request, 'manage_products');
        $data = $this->validateSortOption($request, $sortOption);

        DB::transaction(function () use ($sortOption, $data) {
            $sortOption->update($data['option']);
            if ($sortOption->type !== 'group') {
                $sortOption->products()->detach();
            } elseif (array_key_exists('productIds', $data)) {
                $sortOption->products()->sync($data['productIds']);
            }
        });
        Storefront::forgetCatalog();

        return response()->json(['sortOption' => Storefront::sortOption($sortOption->fresh()), 'sortOptions' => $this->allSortOptions()]);
    }

    public function destroySortOption(Request $request, SortOption $sortOption): JsonResponse
    {
        $this->requirePermission($request, 'delete_products');
        $sortOption->delete();
        Storefront::forgetCatalog();

        return response()->json(['ok' => true, 'sortOptions' => $this->allSortOptions()]);
    }

    /** New order of the menu: every option id, first to last. */
    public function reorderSortOptions(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_products');
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'distinct', 'exists:sort_options,id']]);

        DB::transaction(function () use ($data) {
            foreach (array_values($data['ids']) as $i => $id) {
                SortOption::whereKey($id)->update(['sort_order' => $i]);
            }
        });
        Storefront::forgetCatalog();

        return response()->json(['sortOptions' => $this->allSortOptions()]);
    }

    private function allSortOptions(): array
    {
        return SortOption::with('products:id,sku')->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn ($o) => Storefront::sortOption($o))->all();
    }

    private function validateSortOption(Request $request, ?SortOption $option = null): array
    {
        $req = $option ? 'sometimes' : 'required';
        $data = $request->validate([
            'label' => [$req, 'string', 'min:2', 'max:60'],
            'type' => [$req, Rule::in(['sort', 'group'])],
            'sortKey' => ['nullable', Rule::in(array_keys(SortOption::SORT_KEYS))],
            'freeDelivery' => ['sometimes', 'boolean'],
            'isActive' => ['sometimes', 'boolean'],
            'products' => ['sometimes', 'array', 'max:500'],
            'products.*' => ['string', 'max:64', 'distinct'],
        ], [
            'label.required' => 'Give the option a name, like "New Drop".',
            'label.min' => 'The name needs at least 2 characters.',
        ]);

        $type = $data['type'] ?? $option?->type;
        $out = ['option' => []];
        if (isset($data['label'])) {
            $out['option']['label'] = trim(preg_replace('/\s+/', ' ', $data['label']));
        }
        if (isset($data['type'])) {
            $out['option']['type'] = $data['type'];
        }
        if (array_key_exists('isActive', $data)) {
            $out['option']['is_active'] = $data['isActive'];
        }
        if ($type === 'sort') {
            $key = $data['sortKey'] ?? $option?->sort_key;
            if (! $key) {
                throw ValidationException::withMessages(['sortKey' => 'Choose how this option sorts the products.']);
            }
            $out['option']['sort_key'] = $key;
            $out['option']['free_delivery'] = false;
        } else {
            $out['option']['sort_key'] = null;
            if (array_key_exists('freeDelivery', $data)) {
                $out['option']['free_delivery'] = $data['freeDelivery'];
            }
        }
        if (array_key_exists('products', $data)) {
            $out['productIds'] = collect($data['products'])->map(fn ($key) => Product::findByKey($key)?->id
                ?? throw ValidationException::withMessages(['products' => "Product {$key} was not found."]))->all();
        }

        return $out;
    }

    /* ---------- Home showcase ---------- */

    /** Replaces the showcase picks; the first product is the big front card. */
    public function updateShowcase(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_products');
        $data = $request->validate([
            'products' => ['present', 'array', 'max:'.Product::SHOWCASE_MAX],
            'products.*' => ['string', 'max:64', 'distinct'],
        ], ['products.max' => 'The showcase holds up to '.Product::SHOWCASE_MAX.' products.']);

        $products = collect($data['products'])->map(fn ($key) => Product::findByKey($key) ?? throw ValidationException::withMessages(['products' => "Product {$key} was not found."]));

        DB::transaction(function () use ($products) {
            Product::whereNotNull('showcase_position')->update(['showcase_position' => null]);
            foreach ($products->values() as $i => $product) {
                $product->update(['showcase_position' => $i]);
            }
        });
        Storefront::forgetCatalog();

        return response()->json(['showcase' => $products->map(fn ($p) => $p->sku ?: 'db-'.$p->id)->values()]);
    }

    /* ---------- Orders ---------- */

    public function updateOrder(Request $request, string $orderNumber): JsonResponse
    {
        $this->requirePermission($request, null);
        $data = $request->validate([
            'action' => ['required', Rule::in(['verify', 'reject', 'dispatch', 'deliver', 'cancel'])],
        ]);
        $user = $this->requirePermission($request, match ($data['action']) {
            'verify', 'reject' => 'verify_payments',
            'dispatch', 'deliver' => 'manage_orders',
            'cancel' => 'cancel_orders',
        });

        $order = Order::with(Order::PAGE_RELATIONS)->where('order_number', $orderNumber)->firstOrFail();
        if ($order->order_status === 'cancelled') {
            abort(422, 'This order was already cancelled.');
        }
        $this->checkStep($order, $data['action']);

        DB::transaction(function () use ($order, $data, $user) {
            $payment = $order->latestPayment ?? $order->payments()->create([
                'payment_method_id' => $order->payment_method_id, 'amount_usd' => $order->total_usd, 'status' => 'pending',
            ]);

            match ($data['action']) {
                // Whoever approves the order (the first status change after "placed") becomes the customer's contact.
                'verify' => (function () use ($order, $payment, $user) {
                    $payment->update(['status' => 'verified', 'verified_by' => $user->id, 'paid_at' => $payment->paid_at ?? now()]);
                    $order->moveTo('processing', $user, 'Payment verified');
                })(),
                'reject' => $payment->update(['status' => 'failed', 'verified_by' => $user->id]),
                'dispatch' => $order->moveTo('out_for_delivery', $user),
                'deliver' => (function () use ($order, $payment, $user) {
                    if ($payment->status !== 'verified') { // cash on delivery is paid at the door
                        $payment->update(['status' => 'verified', 'verified_by' => $user->id, 'paid_at' => $payment->paid_at ?? now()]);
                    }
                    $order->moveTo('delivered', $user);
                })(),
                'cancel' => $this->cancelOrder($order, $user),
            };
        });

        Storefront::forgetCatalog();

        return response()->json(['order' => Storefront::order($order->fresh())]);
    }

    /** Only real steps: pending payment -> processing -> out for delivery -> delivered; cancel before delivery. */
    private function checkStep(Order $order, string $action): void
    {
        $status = $order->order_status;
        $payment = $order->payment_status;
        $allowed = match ($action) {
            'verify' => $status === 'pending_payment',
            'reject' => $status === 'pending_payment' && in_array($payment, ['pending', 'slip_uploaded', 'paid_demo'], true),
            'dispatch' => OrderStatus::canMove($status, 'out_for_delivery'),
            'deliver' => OrderStatus::canMove($status, 'delivered'),
            'cancel' => OrderStatus::canMove($status, 'cancelled'),
        };
        if (! $allowed) {
            abort(422, "This order is {$status} and its payment is {$payment}, so \"{$action}\" is not possible now.");
        }
    }

    private function cancelOrder(Order $order, User $by): void
    {
        foreach ($order->items as $item) {
            if ($item->product) {
                Inventory::move($item->product, 'return', $item->quantity, [
                    'by' => $by, 'order_item_id' => $item->id, 'reason' => "Order {$order->order_number} cancelled",
                ]);
            }
        }
        $order->moveTo('cancelled', $by);
    }

    /* ---------- Users ---------- */

    public function storeUser(Request $request): JsonResponse
    {
        $by = $this->requirePermission($request, 'manage_users');
        $data = $this->validateUser($request);
        $this->checkRoleChange($by, null, $data['role']);

        $user = User::create($data);

        return response()->json(['user' => Storefront::user($user)], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $by = $this->requirePermission($request, 'manage_users');
        $data = $this->validateUser($request, $user);

        if ($user->is($by) && (($data['role'] ?? $user->role) !== $user->role || ($data['status'] ?? 'Active') !== 'Active')) {
            throw ValidationException::withMessages(['role' => 'You cannot change your own role or suspend yourself.']);
        }
        $this->checkRoleChange($by, $user, $data['role'] ?? null);
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json(['user' => Storefront::user($user->fresh())]);
    }

    /**
     * People who manage accounts without being an Admin can't touch Admin accounts, can't make anyone
     * an Admin, and can only hand out roles that can't do more than their own.
     */
    private function checkRoleChange(User $by, ?User $target, ?string $role): void
    {
        if ($by->isAdmin()) {
            return;
        }
        if ($target?->isAdmin() || $role === 'admin') {
            abort(403, 'Only an Admin can change Admin accounts or make someone an Admin.');
        }
        if ($role !== null && $role !== $target?->role) {
            $extra = array_diff(Role::permissionsFor(Role::idFor($role)), Role::permissionsFor($by->role_id));
            abort_if($extra !== [], 403, "That role can do things your role can't, so only an Admin can give it.");
        }
    }

    /* ---------- Payment methods ---------- */

    public function storePaymentMethod(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_payment_methods');
        $data = $this->validatePaymentMethod($request);
        $data['code'] = $this->uniqueCode($data['name']);

        $method = PaymentMethod::create($data);

        Storefront::forgetCatalog();

        return response()->json(['paymentMethod' => Storefront::paymentMethod($method)], 201);
    }

    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $this->requirePermission($request, 'manage_payment_methods');
        $paymentMethod->update($this->validatePaymentMethod($request, partial: true));

        Storefront::forgetCatalog();

        return response()->json(['paymentMethod' => Storefront::paymentMethod($paymentMethod)]);
    }

    public function destroyPaymentMethod(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $this->requirePermission($request, 'manage_payment_methods');
        if (in_array($paymentMethod->code, Storefront::PAYMENT_CODES, true)) {
            abort(422, 'Built-in checkout methods cannot be deleted. Turn it off instead.');
        }
        $paymentMethod->delete();
        Storefront::forgetCatalog();

        return response()->json(['ok' => true]);
    }

    /* ---------- Helpers ---------- */

    /** A signed-in, active portal account whose role has this permission ticked (null: any portal account). */
    private function requirePermission(Request $request, ?string $permission): User
    {
        $user = $request->user();
        abort_unless($user, 401, 'Please log in first.');
        abort_unless($user->isStaff() && $user->status !== 'Suspended', 403, 'Your role does not allow this.');
        if ($permission !== null && ! $user->hasPermission($permission)) {
            $label = Permission::label($permission);
            abort(403, "Your role doesn't have the \"{$label}\" permission. Ask an Admin to tick it under Staff & roles.");
        }

        return $user;
    }

    private function findProduct(string $sku): Product
    {
        return Product::findByKey($sku) ?? abort(404, 'Product not found.');
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $partial = $product !== null;
        $req = $partial ? 'sometimes' : 'required';

        $data = $request->validate([
            'title' => [$req, 'string', 'min:2', 'max:255'],
            'titleKhmer' => ['sometimes', 'nullable', 'string', 'max:255'],
            'categoryId' => ['sometimes', 'integer', 'exists:categories,id'],
            'category' => [$partial ? 'sometimes' : 'required_without:categoryId', 'string', 'max:50'],
            'categoryLabel' => ['sometimes', 'nullable', 'string', 'max:100'],
            'priceUSD' => [$req, 'numeric', 'min:0.01', 'max:100000'],
            'discountPercent' => ['sometimes', 'nullable', 'numeric', 'min:1', 'max:90'],
            'discountEndsAt' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
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
            'category.required_without' => 'Product category is required.',
            'priceUSD.required' => 'Price in USD is required.',
            'priceUSD.min' => 'Price must be greater than $0.00.',
            'discountPercent.min' => 'A sale needs at least 1% off.',
            'discountPercent.max' => 'A sale can take at most 90% off.',
            'discountEndsAt.after_or_equal' => 'The sale end date is already past.',
            'stock.required' => 'Stock quantity is required.',
            'stock.min' => 'Stock quantity cannot be negative.',
        ]);

        $out = ['product' => []];
        foreach (['title' => 'title', 'titleKhmer' => 'title_khmer', 'badge' => 'badge', 'status' => 'status'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $out['product'][$column] = $data[$in];
            }
        }
        if (isset($data['priceUSD'])) {
            $out['product']['price_usd'] = round((float) $data['priceUSD'], 2);
        }
        if (array_key_exists('discountPercent', $data)) {
            $out['product']['discount_percent'] = $data['discountPercent'] === null ? null : round((float) $data['discountPercent'], 2);
            if ($data['discountPercent'] === null) {
                $out['product']['discount_ends_at'] = null; // no sale, no end date
            }
        }
        if (array_key_exists('discountEndsAt', $data) && ($out['product']['discount_percent'] ?? $product?->discount_percent) !== null) {
            // A date alone means "until the end of that day".
            $out['product']['discount_ends_at'] = $data['discountEndsAt'] === null ? null
                : (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['discountEndsAt']) ? \Illuminate\Support\Carbon::parse($data['discountEndsAt'])->endOfDay() : $data['discountEndsAt']);
        }
        if (isset($data['categoryId'])) {
            $out['product']['category_id'] = (int) $data['categoryId'];
        } elseif (isset($data['category'])) {
            $out['product']['category_id'] = $this->categoryFor($data['category'], $data['categoryLabel'] ?? null)->id;
        }
        if (array_key_exists('specifications', $data)) {
            $out['specifications'] = $data['specifications'];
        }
        foreach (['description' => 'description'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $out['product'][$column] = $data[$in];
            }
        }
        if (array_key_exists('gallery', $data)) {
            $out['gallery'] = array_values($data['gallery']);
        }
        if (array_key_exists('stock', $data)) {
            $out['stock'] = (int) $data['stock'];
        }

        return $out;
    }

    /** Pictures (1:N, first = primary), facts (1:N) and stock (through a stock movement). */
    private function saveProductParts(Product $product, array $data, User $by): void
    {
        if (array_key_exists('gallery', $data)) {
            $product->images()->delete();
            foreach ($data['gallery'] as $i => $path) {
                $product->images()->create(['image_path' => $path, 'is_primary' => $i === 0, 'sort_order' => $i]);
            }
        }
        // One row per fact (1NF): Material = Stainless steel, Size = 3 cm ...
        if (array_key_exists('specifications', $data)) {
            $product->specifications()->delete();
            $i = 0;
            foreach ($data['specifications'] as $name => $value) {
                $name = mb_substr(trim((string) $name), 0, 100);
                if ($name !== '' && trim((string) $value) !== '') {
                    $product->specifications()->create(['name' => $name, 'value' => trim((string) $value), 'sort_order' => $i++]);
                }
            }
        }
        if (array_key_exists('stock', $data)) {
            Inventory::setQuantity($product, $data['stock'], $by);
        }
    }

    /** "apparel" + "Jeans": the top category by slug, and the label as its sub-category. */
    private function categoryFor(string $slug, ?string $label): Category
    {
        $slug = Str::slug($slug) ?: 'other';
        $top = Category::whereNull('parent_id')->where('slug', $slug)->first()
            ?? Category::create(['slug' => $slug, 'name' => trim((string) $label) ?: Str::headline($slug)]);

        $label = trim((string) $label);
        if ($label === '' || strcasecmp($label, $top->name) === 0) {
            return $top;
        }

        return $top->children()->whereRaw('lower(name) = ?', [mb_strtolower($label)])->first()
            ?? $top->children()->create(['name' => $label, 'slug' => $this->uniqueCategorySlug($slug.'-'.Str::slug($label))]);
    }

    /** Two levels only: a category, or a sub-category of a top-level one. */
    private function validateCategory(Request $request, ?Category $category = null): array
    {
        $req = $category ? 'sometimes' : 'required';
        $data = $request->validate([
            'name' => [$req, 'string', 'min:2', 'max:60'],
            'parentId' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'isActive' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Give the category a name.',
            'name.min' => 'Category names need at least 2 characters.',
        ]);
        if (isset($data['name'])) {
            $data['name'] = trim(preg_replace('/\s+/', ' ', $data['name']));
        }

        $parentId = array_key_exists('parentId', $data) ? $data['parentId'] : $category?->parent_id;
        if ($parentId !== null) {
            $parent = Category::find($parentId);
            if ($category && $parent->id === $category->id) {
                throw ValidationException::withMessages(['parentId' => 'A category cannot be inside itself.']);
            }
            if ($parent->parent_id !== null) {
                throw ValidationException::withMessages(['parentId' => "Sub-categories go one level deep: choose a top-level category instead of \"{$parent->name}\"."]);
            }
            if ($category && $category->children()->exists()) {
                throw ValidationException::withMessages(['parentId' => 'This category has sub-categories of its own, so it has to stay at the top level.']);
            }
        }

        $name = $data['name'] ?? $category?->name;
        $taken = Category::query()
            ->when($parentId === null, fn ($q) => $q->whereNull('parent_id'), fn ($q) => $q->where('parent_id', $parentId))
            ->when($category, fn ($q) => $q->whereKeyNot($category->id))
            ->whereRaw('lower(name) = ?', [mb_strtolower((string) $name)])
            ->exists();
        if ($taken) {
            throw ValidationException::withMessages(['name' => "There is already a category called \"{$name}\" there."]);
        }

        return $data;
    }

    private function uniqueCategorySlug(string $slug): string
    {
        $base = $slug;
        $n = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $req = $user ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => [$req, 'string', 'min:2', 'max:100'],
            'email' => [$req, 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'min:8', 'max:30', 'regex:/^[+0-9\s\-()]+$/', Rule::unique('users', 'phone')->ignore($user?->id)],
            'role' => [$req, 'string', Rule::in(Role::query()->pluck('name')->flatMap(fn ($n) => [$n, Role::label($n)])->all())],
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
            'role.in' => 'That role does not exist any more. Pick another one.',
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
            'qrData' => 'qr_image_url', 'description' => 'description', 'isActive' => 'is_active'];
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
