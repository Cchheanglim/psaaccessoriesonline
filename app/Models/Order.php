<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One checkout. Belongs to a customer, a payment method and an order status; the delivery
 * details are copied onto the order so they stay correct if the customer edits their address.
 * Payment attempts live in payments, every status change in order_status_history.
 *
 * Read-only shortcuts for the pages: order_status (code), payment_status, payment_method (code),
 * payment_slip_url and paid_at (from the latest payment), user_id (the customer's account).
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'handled_by', // staff member who approved it; the customer chats with them
        'payment_method_id',
        'order_status_id',
        'address_id',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'delivery_notes',
        'latitude',
        'longitude',
        'subtotal_usd',
        'discount_usd',
        'points_redeemed',
        'tax_usd',
        'delivery_fee_usd',
        'total_usd',
    ];

    protected $casts = [
        'subtotal_usd' => 'decimal:2',
        'discount_usd' => 'decimal:2',
        'points_redeemed' => 'integer',
        'tax_usd' => 'decimal:2',
        'delivery_fee_usd' => 'decimal:2',
        'total_usd' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function address()
    {
        return $this->belongsTo(CustomerAddress::class, 'address_id');
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
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
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
    public const PAGE_RELATIONS = ['items.product:id,sku', 'handler:id,name', 'status', 'method', 'latestPayment', 'customer'];

    protected function orderStatus(): Attribute
    {
        return Attribute::get(fn () => $this->status?->code ?? OrderStatus::codeFor($this->order_status_id));
    }

    protected function paymentStatus(): Attribute
    {
        return Attribute::get(fn () => $this->latestPayment?->status ?? 'pending');
    }

    protected function paymentMethod(): Attribute
    {
        return Attribute::get(fn () => $this->method?->code);
    }

    protected function paymentSlipUrl(): Attribute
    {
        return Attribute::get(fn () => $this->latestPayment?->slip_url);
    }

    protected function paidAt(): Attribute
    {
        return Attribute::get(fn () => $this->latestPayment?->paid_at);
    }

    protected function userId(): Attribute
    {
        return Attribute::get(fn () => $this->customer?->user_id);
    }

    /** Move the order to a new status and record who did it. */
    public function moveTo(string $statusCode, ?User $by = null, ?string $note = null): void
    {
        $statusId = OrderStatus::idFor($statusCode);
        $this->update(['order_status_id' => $statusId]);
        $this->statusHistory()->create(['order_status_id' => $statusId, 'changed_by' => $by?->id, 'note' => $note]);
        $this->unsetRelation('status');
    }
}
