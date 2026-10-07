<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The steps of an order: pending_payment, processing, out_for_delivery, delivered, cancelled.
 * One row each in order_statuses; order_status_history points at them (order_status_id).
 */
class OrderStatus extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'sort_order'];

    /** code => name, in order (fills the table). */
    public const NAMES = [
        'pending_payment' => 'Pending payment',
        'processing' => 'Processing',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];

    /** Which status may follow which (staff actions can only take real steps). */
    public const NEXT = [
        'pending_payment' => ['processing', 'cancelled'],
        'processing' => ['out_for_delivery', 'delivered', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    /** @var array<string, int>|null code => id, kept for the request */
    private static ?array $ids = null;

    public static function exists(string $code): bool
    {
        return isset(self::NAMES[$code]);
    }

    public static function canMove(string $from, string $to): bool
    {
        return in_array($to, self::NEXT[$from] ?? [], true);
    }

    public static function idFor(string $code): int
    {
        self::$ids ??= static::query()->pluck('id', 'code')->all();

        return self::$ids[$code] ?? throw new \InvalidArgumentException("Unknown order status {$code}");
    }

    /** @return list<int> */
    public static function idsFor(string ...$codes): array
    {
        return array_map(fn ($c) => self::idFor($c), $codes);
    }

    public static function codeFor(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        self::$ids ??= static::query()->pluck('id', 'code')->all();
        $code = array_search($id, self::$ids, true);

        return $code === false ? null : $code;
    }

    public static function flushCache(): void
    {
        self::$ids = null;
    }
}
