<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Everyone who signs in (buyers, staff, admins). What they may do comes from their role;
 * shoppers also have a customer profile (1:1) with their loyalty points and addresses.
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
        'role', // role name ('buyer', 'staff', 'admin'), saved as role_id
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
        // Every account gets its own settings row (theme, language, currency, notifications).
        static::created(fn (User $user) => $user->settings()->create());
    }

    /** The role's name ('buyer', 'staff', 'admin'); setting it stores the matching role_id. */
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

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    /** The shopper profile, created the first time it is needed (e.g. a staff member placing an order). */
    public function customerProfile(): Customer
    {
        if (! $this->customer) {
            $this->setRelation('customer', $this->customer()->create(['loyalty_tier_id' => LoyaltyTier::lowest()->id]));
        }

        return $this->customer;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, Role::permissionsFor($this->role_id), true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'admin'], true);
    }
}
