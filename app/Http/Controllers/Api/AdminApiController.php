<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShowcaseProduct;
use App\Models\SiteSetting;
use App\Models\SortOption;
use App\Models\User;
use App\Support\Inventory;
use App\Support\Notifier;
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
        $user = $this->requireRole($request, 'staff');
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
        $user = $this->requireRole($request, 'staff');
        $product = $this->findProduct($sku);
        $data = $this->validateProduct($request, $product);

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
        $this->requireRole($request, 'admin');
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
        $this->requireRole($request, 'staff');
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
        $this->requireRole($request, 'staff');
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
        $this->requireRole($request, 'staff');
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
        $this->requireRole($request, 'admin');

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
        $this->requireRole($request, 'staff');
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
        $this->requireRole($request, 'staff');
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
        $this->requireRole($request, 'admin');
        $sortOption->delete();
        Storefront::forgetCatalog();

        return response()->json(['ok' => true, 'sortOptions' => $this->allSortOptions()]);
    }

    /** New order of the menu: every option id, first to last. */
    public function reorderSortOptions(Request $request): JsonResponse
    {
        $this->requireRole($request, 'staff');
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

    /* ---------- Home page text ---------- */

    /**
     * The home headline and the line under it. Words between *stars* in the headline get the
     * orange highlight. Empty puts the original text back. Lengths keep the layout intact.
     */
    public function updateSiteContent(Request $request): JsonResponse
    {
        $user = $this->requireRole($request, 'staff');
        $data = $request->validate([
            'homeHeadline' => ['present', 'nullable', 'string', 'max:90'],
            'homeSubtitle' => ['present', 'nullable', 'string', 'max:220'],
        ], [
            'homeHeadline.max' => 'Keep the headline to 90 characters so it fits on phones.',
            'homeSubtitle.max' => 'Keep the line under the headline to 220 characters.',
        ]);
        if (filled($data['homeHeadline']) && mb_strlen(trim(str_replace('*', '', $data['homeHeadline']))) < 3) {
            throw ValidationException::withMessages(['homeHeadline' => 'The headline needs at least 3 characters.']);
        }

        SiteSetting::put(SiteSetting::HOME_HEADLINE, $data['homeHeadline'] === null ? null : preg_replace('/\s+/', ' ', $data['homeHeadline']), $user);
        SiteSetting::put(SiteSetting::HOME_SUBTITLE, $data['homeSubtitle'] === null ? null : preg_replace('/\s+/', ' ', $data['homeSubtitle']), $user);
        Storefront::forgetCatalog();

        return response()->json(['siteContent' => Storefront::siteContent()]);
    }

    /**
     * The shop's social accounts, shown in every footer (and the Telegram one in "message us" links).
     * A platform left out is hidden. null puts the built-in accounts back.
     */
    public function updateSocials(Request $request): JsonResponse
    {
        $user = $this->requireRole($request, 'staff');
        $data = $request->validate([
            'links' => ['present', 'nullable', 'array', 'max:'.count(SiteSetting::SOCIAL_PLATFORMS)],
            'links.*.platform' => ['required', 'distinct', Rule::in(SiteSetting::SOCIAL_PLATFORMS)],
            'links.*.handle' => ['required', 'string', 'max:60'],
            'links.*.url' => ['required', 'string', 'max:255', 'url:https'],
        ], [
            'links.*.handle.required' => 'Add the username (for example @psaonline).',
            'links.*.url.required' => 'Add the link to the account.',
            'links.*.url.url' => 'Links must be full addresses starting with https://',
            'links.*.platform.distinct' => 'Each platform can only be listed once.',
        ]);

        $links = $data['links'] === null ? null : collect($data['links'])->map(fn ($l) => [
            'platform' => $l['platform'],
            'handle' => trim($l['handle']),
            'url' => trim($l['url']),
        ])->values()->all();

        SiteSetting::put(SiteSetting::SOCIAL_LINKS, $links === null ? null : json_encode($links, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $user);
        Storefront::forgetCatalog();

        return response()->json(['siteContent' => Storefront::siteContent()]);
    }

    /* ---------- Home showcase ---------- */

    /** Replaces the showcase picks; the first product is the big front card. */
    public function updateShowcase(Request $request): JsonResponse
    {
        $this->requireRole($request, 'staff');
        $data = $request->validate([
            'products' => ['present', 'array', 'max:'.ShowcaseProduct::MAX],
            'products.*' => ['string', 'max:64', 'distinct'],
        ], ['products.max' => 'The showcase holds up to '.ShowcaseProduct::MAX.' products.']);

        $products = collect($data['products'])->map(fn ($key) => Product::findByKey($key) ?? throw ValidationException::withMessages(['products' => "Product {$key} was not found."]));

        DB::transaction(function () use ($products) {
            ShowcaseProduct::query()->delete();
            foreach ($products->values() as $i => $product) {
                ShowcaseProduct::create(['product_id' => $product->id, 'sort_order' => $i]);
            }
        });
        Storefront::forgetCatalog();

        return response()->json(['showcase' => $products->map(fn ($p) => $p->sku ?: 'db-'.$p->id)->values()]);
    }

    /* ---------- Orders ---------- */

    public function updateOrder(Request $request, string $orderNumber): JsonResponse
    {
        $user = $this->requireRole($request, 'staff');
        $data = $request->validate([
            'action' => ['required', Rule::in(['verify', 'reject', 'dispatch', 'deliver', 'cancel'])],
        ]);

        $order = Order::with(Order::PAGE_RELATIONS)->where('order_number', $orderNumber)->firstOrFail();

        if ($data['action'] === 'cancel' && ! $user->isAdmin()) {
            abort(403, 'Only an Admin can cancel orders.');
        }
        if ($order->order_status === 'cancelled') {
            abort(422, 'This order was already cancelled.');
        }
        $this->checkStep($order, $data['action']);

        DB::transaction(function () use ($order, $data, $user) {
            $payment = $order->latestPayment ?? $order->payments()->create([
                'payment_method_id' => $order->payment_method_id, 'amount_usd' => $order->total_usd, 'status' => 'pending',
            ]);

            match ($data['action']) {
                // Whoever approves the order becomes the customer's contact for it.
                'verify' => (function () use ($order, $payment, $user) {
                    $payment->update(['status' => 'verified', 'verified_by' => $user->id, 'paid_at' => $payment->paid_at ?? now()]);
                    $order->update(['handled_by' => $order->handled_by ?? $user->id]);
                    $order->moveTo('processing', $user, 'Payment verified');
                })(),
                'reject' => $payment->update(['status' => 'failed', 'verified_by' => $user->id]),
                'dispatch' => $order->moveTo('out_for_delivery', $user),
                'deliver' => (function () use ($order, $payment, $user) {
                    if ($payment->status !== 'verified') { // cash on delivery is paid at the door
                        $payment->update(['status' => 'verified', 'verified_by' => $user->id, 'paid_at' => $payment->paid_at ?? now()]);
                    }
                    $order->moveTo('delivered', $user);
                    $this->awardPoints($order);
                })(),
                'cancel' => $this->cancelOrder($order, $user),
            };
        });

        Storefront::forgetCatalog();

        $order->refresh();
        $contact = $order->handler ? explode(' ', trim($order->handler->name))[0] : 'our team';
        [$title, $body] = match ($data['action']) {
            'verify' => [$order->payment_method === 'cod' ? 'Order confirmed' : 'Payment confirmed', "{$contact} approved order {$order->order_number} and it's being prepared. You can message {$contact} from your order page."],
            'reject' => ['Please send a new payment slip', "We couldn't verify the slip for {$order->order_number}. Check the amount and upload the correct screenshot."],
            'dispatch' => ['Out for delivery', "Order {$order->order_number} is on its way to you."],
            'deliver' => ['Delivered', "Order {$order->order_number} was delivered. Enjoy! You can review your items now."],
            'cancel' => ['Order cancelled', "Order {$order->order_number} was cancelled. Message us if you have questions."],
        };
        Notifier::toCustomer($order, 'order_status', $title, $body,
            'order-detail.html?order='.rawurlencode($order->order_number).($data['action'] === 'deliver' ? '#review' : ''));

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
                    'by' => $by, 'unit_price' => $item->unit_price_usd, 'unit_cost' => $item->unit_cost_usd,
                    'order_item_id' => $item->id, 'reason' => "Order {$order->order_number} cancelled",
                ]);
            }
        }
        // Points spent on this order go back to the customer.
        if ($order->customer && $order->points_redeemed > 0) {
            $order->customer->addPoints('adjust', $order->points_redeemed, $order, $by, "Points returned: order {$order->order_number} cancelled");
        }
        $order->moveTo('cancelled', $by);
    }

    /** Delivered orders earn loyalty points (1 per whole dollar x the tier's multiplier) and count toward total spent. */
    private function awardPoints(Order $order): void
    {
        $customer = $order->customer?->loadMissing('tier');
        if (! $customer) {
            return;
        }
        $points = (int) floor((float) $order->total_usd * (float) ($customer->tier?->earn_multiplier ?? 1));
        $customer->update(['total_spent_usd' => round((float) $customer->total_spent_usd + (float) $order->total_usd, 2)]);
        $customer->addPoints('earn', $points, $order, null, "Points for delivered order {$order->order_number}");
    }

    /* ---------- Users ---------- */

    public function storeUser(Request $request): JsonResponse
    {
        $this->requireRole($request, 'admin');
        $data = $this->validateUser($request);

        $user = User::create($data);
        if ($user->role === 'buyer') {
            $user->customerProfile();
        }

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
        if ($user->role === 'buyer') {
            $user->customerProfile();
        }

        return response()->json(['user' => Storefront::user($user->fresh())]);
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
            'stock.required' => 'Stock quantity is required.',
            'stock.min' => 'Stock quantity cannot be negative.',
        ]);

        $out = ['product' => [], 'detail' => []];
        foreach (['title' => 'title', 'titleKhmer' => 'title_khmer', 'badge' => 'badge', 'status' => 'status'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $out['product'][$column] = $data[$in];
            }
        }
        if (isset($data['priceUSD'])) {
            $out['product']['price_usd'] = round((float) $data['priceUSD'], 2);
        }
        if (isset($data['categoryId'])) {
            $out['product']['category_id'] = (int) $data['categoryId'];
        } elseif (isset($data['category'])) {
            $out['product']['category_id'] = $this->categoryFor($data['category'], $data['categoryLabel'] ?? null)->id;
        }
        foreach (['description' => 'description', 'specifications' => 'specifications'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $out['detail'][$column] = $data[$in];
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

    /** Details (1:1), pictures (1:N, first = primary) and stock (through a stock movement). */
    private function saveProductParts(Product $product, array $data, User $by): void
    {
        if ($data['detail']) {
            $product->detail()->updateOrCreate(['product_id' => $product->id], $data['detail']);
        } elseif (! $product->detail()->exists()) {
            $product->detail()->create([]);
        }
        if (array_key_exists('gallery', $data)) {
            $product->images()->delete();
            foreach ($data['gallery'] as $i => $path) {
                $product->images()->create(['image_path' => $path, 'is_primary' => $i === 0, 'sort_order' => $i]);
            }
        }
        if (array_key_exists('stock', $data)) {
            Inventory::setQuantity($product, $data['stock'], $by);
        } else {
            Inventory::lock($product); // makes sure the stock row exists
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
