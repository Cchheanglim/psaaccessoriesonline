<?php

namespace App\Support;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Converts database rows into the JSON shapes the storefront pages
 * (public/*.html + assets/js/store.js) were written against.
 */
class Storefront
{
    public const EXCHANGE_RATE = 4100;

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

    /** Products + payment methods, cached for 30 seconds. Staff also see draft/archived products. */
    public static function catalog(bool $includeHidden): array
    {
        return Cache::remember('storefront.catalog.'.($includeHidden ? 'all' : 'public'), 30, fn () => [
            'products' => Product::query()
                ->when(! $includeHidden, fn ($q) => $q->where('status', 'active'))
                ->orderByRaw('sku is null')->orderBy('sku')->orderBy('id')
                ->get()->map(fn ($p) => self::product($p))->all(),
            'paymentMethods' => PaymentMethod::orderBy('id')->get()->map(fn ($m) => self::paymentMethod($m))->all(),
        ]);
    }

    public static function forgetCatalog(): void
    {
        Cache::forget('storefront.catalog.all');
        Cache::forget('storefront.catalog.public');
    }

    public static function product(Product $p): array
    {
        $gallery = $p->gallery ?: array_values(array_filter([$p->image]));

        return [
            'id' => $p->sku ?: 'db-'.$p->id,
            'dbId' => $p->id,
            'title' => $p->title,
            'titleKhmer' => $p->title_khmer,
            'category' => $p->category,
            'categoryLabel' => $p->category_label ?: ucfirst($p->category),
            'priceUSD' => (float) $p->price_usd,
            'priceKHR' => (int) $p->price_khr,
            'inStock' => $p->stock > 0,
            'stock' => (int) $p->stock,
            'rating' => (float) $p->rating,
            'reviewsCount' => (int) $p->review_count,
            'badge' => $p->badge,
            'image' => $p->image,
            'gallery' => $gallery,
            'description' => $p->description,
            'specifications' => (object) ($p->specifications ?: []),
            'status' => $p->status,
        ];
    }

    public static function user(User $u): array
    {
        $role = ucfirst($u->role);

        return [
            'id' => (string) $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'role' => $role,
            'avatar' => self::initials($u->name),
            'avatarUrl' => $u->avatar,
            'bannerUrl' => $u->banner,
            'address' => $u->address,
            'status' => $u->status ?: 'Active',
            'created' => optional($u->created_at)->format('d M Y'),
            'department' => [
                'Admin' => 'Studio Management & Finance',
                'Staff' => 'Fulfillment & Catalog Operations',
            ][$role] ?? 'Customer',
            'description' => [
                'Admin' => 'Full access: reports, payments, catalog and team roles.',
                'Staff' => 'Verifies payment slips, dispatches orders and manages stock.',
            ][$role] ?? 'Registered PsaOnline shopper.',
        ];
    }

    public static function order(Order $o): array
    {
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
            'items' => $o->items->map(fn ($i) => [
                'id' => $i->product_id,
                'title' => $i->product_title,
                'image' => $i->product_image,
                'priceUSD' => (float) $i->price_usd,
                'priceKHR' => (int) $i->price_khr,
                'quantity' => (int) $i->quantity,
            ])->all(),
            'subtotalUSD' => number_format((float) $o->subtotal_usd, 2, '.', ''),
            'deliveryUSD' => number_format((float) $o->delivery_fee_usd, 2, '.', ''),
            'totalUSD' => number_format((float) $o->total_usd, 2, '.', ''),
            'totalKHR' => number_format((int) $o->total_khr),
            'status' => self::statusLabel($o),
            'paymentMethod' => self::paymentName($o->payment_method),
            'paymentCode' => $o->payment_method,
            'paymentStatus' => $o->payment_status,
            'orderStatus' => $o->order_status,
            'slipUploaded' => (bool) $o->payment_slip_url,
            'slipImage' => $o->payment_slip_url,
            'reviewed' => (bool) ($o->reviews_count ?? $o->reviews()->exists()),
            'messageCount' => (int) ($o->messages_count ?? $o->messages()->count()),
        ];
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
            'qrData' => $m->qr_data,
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
