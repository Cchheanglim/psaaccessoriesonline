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

    /** @return list<string> the permission names ticked for this role */
    public function permissionNames(): array
    {
        return self::permissionsFor($this->id);
    }

    /** Replaces the ticked permissions (names from Permission::LIST). */
    public function syncPermissions(array $names): void
    {
        \Illuminate\Support\Facades\DB::table('role_permissions')->where('role_id', $this->id)->delete();
        $rows = array_map(fn ($n) => ['role_id' => $this->id, 'permission' => $n], array_values(array_intersect(Permission::names(), $names)));
        if ($rows) {
            \Illuminate\Support\Facades\DB::table('role_permissions')->insert($rows);
        }
        self::flushCache();
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

        return self::$permissions[$id] ??= array_values(array_intersect(Permission::names(),
            \Illuminate\Support\Facades\DB::table('role_permissions')->where('role_id', $id)->pluck('permission')->all()));
    }

    public static function flushCache(): void
    {
        self::$ids = null;
        self::$permissions = [];
    }
}
