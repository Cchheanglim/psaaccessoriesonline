<?php

namespace App\Support;

use App\Models\Category;
use App\Models\LoyaltyTier;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\SortOption;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Converts database rows into the JSON shapes the storefront pages
 * (public/*.html + assets/js/store.js) were written against. The tables changed in the
 * 2026-10 redesign; these shapes did not, so the pages keep working unchanged.
 */
class Storefront
{
    /** Checkout radio value => payment_methods.code */
    public const PAYMENT_CODES = [
        'khqr' => 'bakong_khqr',
        'aba' => 'aba_pay',
        'acleda' => 'acleda_khqr',
        'visa' => 'visa_card',
        'cod' => 'cod',
    ];

    public const PAYMENT_LABELS = [
        'bakong_khqr' => 'Bakong Universal KHQR',
        'aba_pay' => 'ABA Mobile Pay',
        'acleda_khqr' => 'ACLEDA Mobile KHQR',
        'visa_card' => 'Visa / Mastercard',
        'cod' => 'Cash on Delivery',
    ];

    /** Relations a product card needs. */
    public const PRODUCT_RELATIONS = ['category.parent', 'images', 'specifications', 'freeDeliveryGroups'];

    /** Delivery in Phnom Penh: the fee and the free-delivery amount are site settings (Website > Shop rules). */
    public static function deliveryFee(float $subtotal, bool $hasFreeDeliveryItem): float
    {
        $freeFrom = SiteSetting::number('shop.free_delivery_from_usd');

        return ($hasFreeDeliveryItem || $subtotal >= $freeFrom) ? 0.0 : round(SiteSetting::number('shop.delivery_fee_usd'), 2);
    }

    /**
     * Products, categories, the home showcase and payment methods, cached for 30 seconds.
     * Staff also see draft/archived products and hidden categories; customers don't see
     * products whose category (or its parent) is hidden.
     */
    public static function catalog(bool $includeHidden): array
    {
        return Cache::remember('storefront.catalog.'.($includeHidden ? 'all' : 'public'), 30, function () use ($includeHidden) {
            $products = Product::query()
                ->with(self::PRODUCT_RELATIONS)
                ->withAvg('reviews', 'rating')->withCount('reviews')
                ->withSum('stockMovements', 'quantity_change')
                ->when(! $includeHidden, fn ($q) => $q->where('status', 'active')->whereHas('category', fn ($c) => $c
                    ->where('is_active', true)
                    ->where(fn ($c) => $c->whereNull('parent_id')->orWhereHas('parent', fn ($p) => $p->where('is_active', true)))))
                ->orderByRaw('sku is null')->orderBy('sku')->orderBy('id')
                ->get()->map(fn ($p) => self::product($p))->all();

            $shown = array_flip(array_column($products, 'id'));
            $showcase = Product::whereNotNull('showcase_position')->orderBy('showcase_position')->get(['id', 'sku'])
                ->map(fn ($p) => $p->sku ?: 'db-'.$p->id)
                ->filter(fn ($id) => isset($shown[$id]))->values()->all();

            $sortOptions = SortOption::with('products:id,sku')
                ->when(! $includeHidden, fn ($q) => $q->where('is_active', true))
                ->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn ($o) => self::sortOption($o, $includeHidden ? null : $shown))->all();

            return [
                'products' => $products,
                'categories' => self::categories($includeHidden),
                'showcase' => $showcase,
                'sortOptions' => $sortOptions,
                'settings' => SiteSetting::map(),
                'paymentMethods' => PaymentMethod::orderBy('id')->get()->map(fn ($m) => self::paymentMethod($m))->all(),
            ];
        });
    }

    /** Every category, flat (parentId links a sub-category to its parent), A to Z. */
    public static function categories(bool $includeHidden): array
    {
        $list = Category::query()
            ->withCount(['products' => fn ($q) => $includeHidden ? $q->where('status', '!=', 'archived') : $q->where('status', 'active')])
            ->orderBy('name')
            ->get();
        $active = $list->pluck('is_active', 'id');

        return $list
            ->filter(fn ($c) => $includeHidden || ($c->is_active && (! $c->parent_id || ($active[$c->parent_id] ?? false))))
            ->map(fn ($c) => self::category($c))
            ->values()->all();
    }

    /** A customer's tier (Plus / Pro / Max), how far to the next tier and when the membership ends. */
    public static function loyalty(User $customer): array
    {
        $membership = $customer->membership; // from the customer's orders
        $tier = $customer->tier;
        $lowest = LoyaltyTier::lowest();
        $next = $tier->next();
        $usd = fn ($v) => number_format((float) $v, 2, '.', '');

        return [
            'tier' => $tier->name,
            'discountPercent' => (float) $tier->discount_percent,
            'spendUSD' => $usd($membership['spend']),
            'tierFromUSD' => $usd($tier->min_spend_usd),
            'nextTier' => $next?->name,
            'nextTierAtUSD' => $next ? $usd($next->min_spend_usd) : null,
            'spendToNextUSD' => $next ? $usd(max(0, (float) $next->min_spend_usd - $membership['spend'])) : null,
            'lastOrderAt' => optional($membership['lastOrderAt'])->toIso8601String(),
            // Pro and Max end on this day unless the customer orders again
            'expiresAt' => $tier->name !== $lowest->name ? optional($membership['expiresAt'])->toIso8601String() : null,
            'lapseDays' => LoyaltyTier::lapseDays(),
            'totalSpentUSD' => $usd($customer->total_spent_usd),
            'tiers' => array_map(fn (LoyaltyTier $t) => [
                'name' => $t->name, 'minSpendUSD' => $t->min_spend_usd, 'discountPercent' => $t->discount_percent,
            ], LoyaltyTier::all()),
        ];
    }

    /** One Sort By choice. $shown limits a group's products to the ones customers can see. */
    public static function sortOption(SortOption $o, ?array $shown = null): array
    {
        $o->loadMissing('products:id,sku');
        $ids = $o->products->map(fn ($p) => $p->sku ?: 'db-'.$p->id)
            ->filter(fn ($id) => $shown === null || isset($shown[$id]))->values()->all();

        return [
            'id' => $o->id,
            'label' => $o->label,
            'type' => $o->type,
            'sortKey' => $o->sort_key,
            'freeDelivery' => $o->type === 'group' && $o->free_delivery,
            'isActive' => (bool) $o->is_active,
            'products' => $o->type === 'group' ? $ids : [],
        ];
    }

    public static function category(Category $c): array
    {
        return [
            'id' => $c->id,
            'slug' => $c->slug,
            'name' => $c->name,
            'parentId' => $c->parent_id,
            'isActive' => (bool) $c->is_active,
            'productCount' => (int) ($c->products_count ?? $c->products()->count()),
        ];
    }

    public static function forgetCatalog(): void
    {
        Cache::forget('storefront.catalog.all');
        Cache::forget('storefront.catalog.public');
    }

    public static function product(Product $p): array
    {
        $p->loadMissing(self::PRODUCT_RELATIONS);
        if (! array_key_exists('reviews_count', $p->getAttributes())) {
            $p->loadAvg('reviews', 'rating')->loadCount('reviews');
        }
        $category = $p->category;
        $root = $category?->root();
        $gallery = $p->images->pluck('image_path')->values()->all();
        $stock = $p->stock_on_hand; // SUM of its stock movements

        return [
            'id' => $p->sku ?: 'db-'.$p->id,
            'dbId' => $p->id,
            'title' => $p->title,
            'titleKhmer' => $p->title_khmer,
            'category' => $root?->slug,
            'categoryLabel' => $category?->name ?? ucfirst((string) $root?->slug),
            'categoryId' => $category?->id,
            'priceUSD' => $p->sellingPrice(), // what customers pay now (sale included)
            'basePriceUSD' => (float) $p->price_usd, // the normal price staff set
            'originalPriceUSD' => $p->activeDiscountPercent() !== null ? (float) $p->price_usd : null, // crossed out while on sale
            'discountPercent' => $p->discount_percent !== null ? (float) $p->discount_percent : null,
            'discountEndsAt' => optional($p->discount_ends_at)->toIso8601String(),
            'onSale' => $p->activeDiscountPercent() !== null,
            'inStock' => $stock > 0,
            'stock' => $stock,
            'rating' => round((float) $p->reviews_avg_rating, 1),
            'reviewsCount' => (int) $p->reviews_count,
            'badge' => $p->badge,
            'image' => $p->primaryImagePath(),
            'gallery' => $gallery,
            'description' => $p->description,
            'specifications' => (object) $p->specifications->pluck('value', 'name')->all(),
            'status' => $p->status,
            'freeDelivery' => $p->freeDeliveryGroups->isNotEmpty(),
        ];
    }

    public static function user(User $u): array
    {
        $role = Role::label($u->role);
        $u->loadMissing('defaultAddress', 'roleRecord');
        $shopper = ! $u->isStaff();

        return [
            'id' => (string) $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'role' => $role,
            'avatar' => self::initials($u->name),
            'avatarUrl' => $u->avatar_url,
            'bannerUrl' => $u->banner_url,
            'address' => $u->defaultAddress?->address_line,
            'status' => $u->status ?: 'Active',
            'created' => optional($u->created_at)->format('d M Y'),
            'department' => $u->isStaff() ? $role.' team' : 'Customer',
            'description' => $u->roleRecord?->description ?: 'Registered PsaOnline shopper.',
            'permissions' => $u->permissionNames(),
            'loyalty' => $shopper ? self::loyalty($u) : null,
        ];
    }

    public static function order(Order $o): array
    {
        $o->loadMissing(Order::PAGE_RELATIONS);
        $payment = $o->latestPayment;

        return [
            'id' => $o->order_number,
            'dbId' => $o->id,
            'userId' => $o->user_id ? (string) $o->user_id : null,
            'date' => optional($o->created_at)->timezone(config('app.timezone'))->format('d M Y, H:i'),
            'createdAt' => optional($o->created_at)->toIso8601String(),
            'customerName' => $o->customer_name,
            'phone' => $o->customer_phone,
            'address' => $o->delivery_address,
            'notes' => $o->delivery_notes,
            'latitude' => $o->latitude,
            'longitude' => $o->longitude,
            'items' => $o->items->map(fn ($i) => [
                'id' => $i->product?->sku ?? ($i->product_id ? 'db-'.$i->product_id : null),
                'title' => $i->product?->title,
                'image' => $i->product?->primaryImagePath(),
                'priceUSD' => (float) $i->unit_price_usd,
                'quantity' => (int) $i->quantity,
            ])->all(),
            'subtotalUSD' => number_format($o->subtotal_usd, 2, '.', ''),
            'memberDiscountUSD' => number_format((float) $o->member_discount_usd, 2, '.', ''),
            'discountUSD' => number_format($o->promo_discount_usd, 2, '.', ''),
            'promoCode' => $o->promoCode?->code,
            'deliveryUSD' => number_format((float) $o->delivery_fee_usd, 2, '.', ''),
            'totalUSD' => number_format($o->total_usd, 2, '.', ''),
            'status' => self::statusLabel($o),
            'paymentMethod' => self::paymentName($o->payment_method),
            'paymentCode' => $o->payment_method,
            'paymentStatus' => $o->payment_status,
            'orderStatus' => $o->order_status,
            'slipUploaded' => $payment && ($payment->slip_url !== null || $payment->status === 'slip_uploaded'),
            'slipImage' => $payment?->slip_url,
            'paidAt' => optional($payment?->paid_at)->toIso8601String(),
            'reviewed' => (bool) ($o->reviews_count ?? $o->reviews()->exists()),
            'messageCount' => (int) ($o->messages_count ?? $o->messages()->count()),
            // Staff replies the customer hasn't opened yet
            'unreadReplies' => (int) ($o->unread_replies_count ?? OrderMessage::where('order_id', $o->id)->fromStaff()->whereNull('read_at')->count()),
            'handledBy' => $o->handler?->name,
            'handledById' => $o->handled_by ? (string) $o->handled_by : null,
        ];
    }

    /** Promo codes staff chose to show customers under "My coupons" that can be used now. */
    public static function coupons(): array
    {
        return PromoCode::where('is_active', true)->where('show_to_customers', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('ends_at')->orderByDesc('id')->limit(20)->get()
            ->filter(fn (PromoCode $p) => $p->state() === 'active')
            ->map(fn (PromoCode $p) => [
                'code' => $p->code,
                'label' => $p->label(),
                'description' => $p->description,
                'minOrderUSD' => $p->min_order_usd !== null ? (float) $p->min_order_usd : null,
                'endsAt' => optional($p->ends_at)->toIso8601String(),
            ])->values()->all();
    }

    /** Display name for a payment code: built-in label, else the admin-created method's name. */
    public static function paymentName(?string $code): ?string
    {
        static $names = null;
        if (isset(self::PAYMENT_LABELS[$code])) {
            return self::PAYMENT_LABELS[$code];
        }
        $names ??= PaymentMethod::pluck('name', 'code')->all();

        return $names[$code] ?? $code;
    }

    public static function paymentMethod(PaymentMethod $m): array
    {
        return [
            'id' => $m->id,
            'name' => $m->name,
            'code' => $m->code,
            'type' => $m->type,
            'accountName' => $m->account_name,
            'accountNumber' => $m->account_number,
            'qrData' => $m->qr_image_url,
            'description' => $m->description,
            'icon' => $m->icon,
            'isActive' => (bool) $m->is_active,
        ];
    }

    public static function statusLabel(Order $o): string
    {
        return match ($o->order_status) {
            'cancelled' => 'Cancelled',
            'delivered' => 'Delivered',
            'out_for_delivery' => 'Out for delivery',
            'processing' => 'Processing',
            default => match ($o->payment_status) {
                'slip_uploaded' => 'Slip Uploaded',
                'verified' => 'Payment Verified',
                'paid_demo' => 'Paid (demo)',
                'failed' => 'Payment Failed',
                'refunded' => 'Refunded',
                default => 'Payment Pending',
            },
        };
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first.$last) ?: '?';
    }

    /** Validation rule: an uploaded image (data URL), an http(s) link, or one of the site's own images (/assets/...). */
    public static function imageRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if ($value !== null && ! preg_match('#^(data:image/(png|jpe?g|gif|webp);base64,|https?://|/assets/[\w./-]+$)#i', $value)) {
                $fail('The image must be an uploaded picture or an http(s) link.');
            }
        };
    }
}
