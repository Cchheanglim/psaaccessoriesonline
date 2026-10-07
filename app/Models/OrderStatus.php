<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** pending_payment, processing, out_for_delivery, delivered, cancelled. */
class OrderStatus extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'sort_order'];

    /** @var array<string, int>|null code => id, kept for the request */
    private static ?array $ids = null;

    /** Which status may follow which (staff actions can only take real steps). */
    public const NEXT = [
        'pending_payment' => ['processing', 'cancelled'],
        'processing' => ['out_for_delivery', 'delivered', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public static function idFor(string $code): int
    {
        self::$ids ??= static::query()->pluck('id', 'code')->all();

        return self::$ids[$code] ?? throw new \InvalidArgumentException("Unknown order status {$code}");
    }

    public static function codeFor(?int $id): ?string
    {
        self::$ids ??= static::query()->pluck('id', 'code')->all();
        $code = array_search($id, self::$ids, true);

        return $code === false ? null : $code;
    }

    public static function canMove(string $from, string $to): bool
    {
        return in_array($to, self::NEXT[$from] ?? [], true);
    }

    public static function flushCache(): void
    {
        self::$ids = null;
    }
}
