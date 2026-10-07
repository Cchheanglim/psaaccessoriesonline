<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One checkout (3NF). The order stores only its own facts: who ordered (customer_id), where it ships (address_id),
 * notes, and the delivery fee, member discount and promo code decided at checkout. Everything else is looked up or calculated:
 *
 *   customer_name, customer_phone, delivery_address, latitude, longitude  <- the address it ships to
 *   subtotal_usd, total_usd                                               <- its items
 *   order_status                                                          <- newest order_status_history row
 *   handled_by / handler                                                  <- who made the first change after "placed"
 *   payment_method / payment_status / payment_slip_url / paid_at          <- newest payments row
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'user_id', // sets customer_id: the customer row of that user
        'address_id',
        'promo_code_id',
        'delivery_notes',
        'delivery_fee_usd',
        'member_discount_usd', // taken off by the customer's membership (Pro / Max) at that moment
    ];

    protected $casts = [
        'member_discount_usd' => 'decimal:2',
        'delivery_fee_usd' => 'decimal:2',
    ];

    /** The shopper who placed it. */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /** The shopper's account (through customers). */
    public function user()
    {
        return $this->hasOneThrough(User::class, Customer::class, 'id', 'id', 'customer_id', 'user_id');
    }

    /** The shopper's user id (orders store customer_id; setting it finds or makes the customer row). */
    protected function userId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->customer?->user_id,
            set: fn (int $id) => ['customer_id' => Customer::idForUser($id)],
        );
    }

    /** Orders placed by this user (as a customer). */
    public function scopeOfUser($query, int $userId)
    {
        return $query->whereIn('customer_id', Customer::select('id')->where('user_id', $userId));
    }

    public function address()
    {
        return $this->belongsTo(CustomerAddress::class, 'address_id');
    }

    /** The promo code typed at checkout, if any (what it took off is calculated: promo_discount_usd). */
    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    /** The order's current status: its newest history row. */
    public function currentStatus()
    {
        return $this->hasOne(OrderStatusHistory::class)->latestOfMany();
    }

    /** The first change after the order was placed; whoever made it handles the order. */
    public function approval()
    {
        return $this->hasOne(OrderStatusHistory::class)->ofMany(['id' => 'min'], function ($q) {
            $q->where('order_status_id', '!=', OrderStatus::idFor('pending_payment'))->whereNotNull('changed_by');
        });
    }

    public function messages()
    {
        return $this->hasMany(OrderMessage::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    /** Relations the pages need, to load in one go. */
    public const PAGE_RELATIONS = ['items.product:id,sku,title', 'items.product.images', 'address', 'currentStatus', 'approval.changedBy:id,name', 'latestPayment.method', 'customer', 'promoCode'];

    /** Only orders whose current (newest) status is one of these codes. */
    public function scopeInStatus($query, string ...$codes)
    {
        return $query->whereHas('currentStatus', fn ($q) => $q->whereIn('order_status_id', OrderStatus::idsFor(...$codes)));
    }

    /* ---------- From the address it ships to ---------- */

    protected function customerName(): Attribute
    {
        return Attribute::get(fn () => $this->address?->recipient_name);
    }

    protected function customerPhone(): Attribute
    {
        return Attribute::get(fn () => $this->address?->phone);
    }

    protected function deliveryAddress(): Attribute
    {
        return Attribute::get(fn () => $this->address?->address_line);
    }

    protected function latitude(): Attribute
    {
        return Attribute::get(fn () => $this->address?->latitude !== null ? (float) $this->address->latitude : null);
    }

    protected function longitude(): Attribute
    {
        return Attribute::get(fn () => $this->address?->longitude !== null ? (float) $this->address->longitude : null);
    }

    /* ---------- From its items ---------- */

    protected function subtotalUsd(): Attribute
    {
        return Attribute::get(fn () => round($this->items->sum(fn (OrderItem $i) => $i->lineTotal()), 2));
    }

    /** What the promo code took off: its discount on the items left after the member discount. */
    protected function promoDiscountUsd(): Attribute
    {
        return Attribute::get(fn () => $this->promoCode
            ? $this->promoCode->discountFor($this->subtotal_usd - (float) $this->member_discount_usd)
            : 0.0);
    }

    protected function totalUsd(): Attribute
    {
        return Attribute::get(fn () => round($this->subtotal_usd - (float) $this->member_discount_usd - $this->promo_discount_usd + (float) $this->delivery_fee_usd, 2));
    }

    /* ---------- From the status history ---------- */

    protected function orderStatus(): Attribute
    {
        return Attribute::get(fn () => $this->currentStatus?->status);
    }

    protected function handledBy(): Attribute
    {
        return Attribute::get(fn () => $this->approval?->changed_by);
    }

    protected function handler(): Attribute
    {
        return Attribute::get(fn () => $this->approval?->changedBy);
    }

    /* ---------- From the payments ---------- */

    protected function paymentMethodId(): Attribute
    {
        return Attribute::get(fn () => $this->latestPayment?->payment_method_id);
    }

    protected function paymentMethod(): Attribute
    {
        return Attribute::get(fn () => $this->latestPayment?->method?->code);
    }

    protected function paymentStatus(): Attribute
    {
        return Attribute::get(fn () => $this->latestPayment?->status ?? 'pending');
    }

    protected function paymentSlipUrl(): Attribute
    {
        return Attribute::get(fn () => $this->latestPayment?->slip_url);
    }

    protected function paidAt(): Attribute
    {
        return Attribute::get(fn () => $this->latestPayment?->paid_at);
    }

    /* ---------- Other ---------- */

    /** Move the order to a new status and record who did it. */
    public function moveTo(string $statusCode, ?User $by = null, ?string $note = null): void
    {
        if (! OrderStatus::exists($statusCode)) {
            throw new \InvalidArgumentException("Unknown order status {$statusCode}");
        }
        $this->statusHistory()->create(['status' => $statusCode, 'changed_by' => $by?->id, 'note' => $note]);
        $this->unsetRelation('currentStatus');
        $this->unsetRelation('approval');
    }
}
