<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * "role" ('buyer', 'staff', 'admin') is deliberately absent: it must only
     * ever be set by explicit assignment, so that passing request input into
     * create() or update() can never promote someone to admin.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Up to two initials from the user's name, for avatar placeholders.
     */
    public function initials(): string
    {
        return collect(preg_split('/\s+/u', trim((string) $this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join('');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * True for staff and admins: anyone allowed into the operations portal.
     */
    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'admin'], true);
    }
}
