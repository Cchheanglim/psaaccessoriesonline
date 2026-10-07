<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Everyone who signs in (buyers, staff, admins). What they may do comes from their role.
 * A shopper also has a customers row (1:1); their addresses, orders and reviews point at it. Their
 * membership tier and total spent are calculated from their orders (nothing extra is stored).
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role_id',
        'role', // role name ('buyer', 'staff', 'admin' or one an admin made), saved as role_id
        'avatar_url',
        'banner_url',
        'status', // 'Active', 'Suspended'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->role_id ??= Role::idFor('buyer');
        });
        // A shopper account is a customer (staff get a customer row only if they ever shop).
        static::created(function (User $user) {
            if ($user->role === 'buyer') {
                Customer::firstOrCreate(['user_id' => $user->id]);
            }
        });
    }

    /** The role's name in lower case ('buyer', 'staff', 'admin', ...); setting it stores the matching role_id. */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn () => Role::nameFor($this->role_id),
            set: fn (?string $value) => ['role_id' => Role::idFor(strtolower((string) $value) ?: 'buyer')],
        );
    }

    public function roleRecord()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /* ---------- As a shopper (through their customers row) ---------- */

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    /** The customer row, made the first time it is needed. */
    public function asCustomer(): Customer
    {
        $customer = $this->customer ?? Customer::firstOrCreate(['user_id' => $this->id]);
        $this->setRelation('customer', $customer);

        return $customer;
    }

    public function addresses()
    {
        return $this->hasManyThrough(CustomerAddress::class, Customer::class);
    }

    public function defaultAddress()
    {
        return $this->hasOneThrough(CustomerAddress::class, Customer::class)
            ->where('customer_addresses.is_default', true)->whereNull('customer_addresses.archived_at');
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, Customer::class);
    }

    public function reviews()
    {
        return $this->hasManyThrough(ProductReview::class, Customer::class);
    }

    /**
     * Where the membership stands, worked out from the user's orders:
     *  - spend: delivered orders since the membership last lapsed. A gap of more than
     *    LoyaltyTier::lapseDays() between orders (or since the last one) starts the count again.
     *  - lastOrderAt / expiresAt: the newest order (cancelled ones don't count) and that many days after it.
     *
     * @return array{spend: float, lastOrderAt: mixed, expiresAt: mixed}
     */
    protected function membership(): Attribute
    {
        return Attribute::get(function () {
            $orders = $this->orders()->with(['items', 'currentStatus'])->orderBy('orders.created_at')->orderBy('orders.id')->get()
                ->reject(fn (Order $o) => $o->currentStatus?->status === 'cancelled')->values();

            $spend = 0.0;
            $previous = null;
            $lapse = LoyaltyTier::lapseDays();
            foreach ($orders as $order) {
                if ($previous && $previous->created_at->diffInDays($order->created_at, true) > $lapse) {
                    $spend = 0.0; // the membership had lapsed before this order
                }
                if ($order->currentStatus?->status === 'delivered') {
                    $spend += (float) $order->total_usd;
                }
                $previous = $order;
            }
            $expiresAt = $previous?->created_at->copy()->addDays($lapse);
            if ($expiresAt && $expiresAt->isPast()) {
                $spend = 0.0;
            }

            return ['spend' => round($spend, 2), 'lastOrderAt' => $previous?->created_at, 'expiresAt' => $expiresAt];
        })->shouldCache();
    }

    /** The tier the current spending reaches (Plus, Pro or Max). */
    protected function tier(): Attribute
    {
        return Attribute::get(fn () => LoyaltyTier::forSpend($this->membership['spend']))->shouldCache();
    }

    /** What the user has spent on delivered orders. */
    protected function totalSpentUsd(): Attribute
    {
        return Attribute::get(fn () => round(
            $this->orders()->inStatus('delivered')->with('items')->get()->sum(fn (Order $o) => $o->total_usd), 2
        ))->shouldCache();
    }

    /* ---------- As staff ---------- */

    /** Admins can do everything; everyone else gets what their role's ticked permissions allow. */
    public function hasPermission(string $permission): bool
    {
        return $this->isAdmin() || in_array($permission, Role::permissionsFor($this->role_id), true);
    }

    /** @return list<string> */
    public function permissionNames(): array
    {
        if ($this->isAdmin()) {
            return Permission::names();
        }

        return $this->isStaff() ? Role::permissionsFor($this->role_id) : [];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Every role except buyer (staff, admin and roles an admin created) works in the staff portal. */
    public function isStaff(): bool
    {
        $role = $this->role;

        return $role !== null && $role !== 'buyer';
    }
}
