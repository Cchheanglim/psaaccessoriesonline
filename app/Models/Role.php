<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * buyer, staff, admin, plus any role an admin creates. Permissions are linked through
 * role_permissions (many-to-many). Admin always has every permission; buyer works only in the shop.
 */
class Role extends Model
{
    /** Roles the website needs; they can't be renamed or deleted. */
    public const BUILT_IN = ['admin', 'staff', 'buyer'];

    /** Roles whose permissions are fixed: admin has all of them, buyer none. */
    public const LOCKED = ['admin', 'buyer'];

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** @var array<string, int>|null name => id, kept for the request */
    private static ?array $ids = null;

    /** @var array<int, list<string>> role id => permission names */
    private static array $permissions = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /** "warehouse team" -> "Warehouse team", how the name is shown and sent to the pages. */
    public static function label(?string $name): string
    {
        return ucfirst((string) $name);
    }

    public static function idFor(string $name): ?int
    {
        self::$ids ??= static::query()->pluck('id', 'name')->all();

        return self::$ids[$name] ?? null;
    }

    public static function nameFor(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        self::$ids ??= static::query()->pluck('id', 'name')->all();
        $name = array_search($id, self::$ids, true);
        if ($name === false) { // a role added after the cache was filled
            self::$ids = static::query()->pluck('id', 'name')->all();
            $name = array_search($id, self::$ids, true);
        }

        return $name === false ? null : $name;
    }

    /** @return list<string> */
    public static function permissionsFor(?int $id): array
    {
        if ($id === null) {
            return [];
        }

        return self::$permissions[$id] ??= Permission::query()
            ->join('role_permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $id)->where('permissions.is_active', true)
            ->pluck('permissions.name')->all();
    }

    public static function flushCache(): void
    {
        self::$ids = null;
        self::$permissions = [];
    }
}
