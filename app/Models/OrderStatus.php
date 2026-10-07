<?php

namespace App\Models;

/**
 * The steps of an order. Not a table: order_status_history.status stores the code (checked by the
 * database), and the names and allowed steps are fixed here.
 */
class OrderStatus
{
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

    public static function exists(string $code): bool
    {
        return isset(self::NAMES[$code]);
    }

    public static function canMove(string $from, string $to): bool
    {
        return in_array($to, self::NEXT[$from] ?? [], true);
    }
}
