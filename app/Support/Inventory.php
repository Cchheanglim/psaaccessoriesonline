<?php

namespace App\Support;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The only place stock changes. Stock is not stored anywhere: it is the sum of the product's
 * stock_movements, and every change is one new movement row. The product row is locked while
 * checking, so two orders at the same moment cannot sell the last item twice.
 * Call inside a DB transaction when other rows change too.
 */
class Inventory
{
    /** Lock the product and return the units on hand right now. */
    public static function lock(Product $product): int
    {
        Product::whereKey($product->id)->lockForUpdate()->first();

        return (int) StockMovement::where('product_id', $product->id)->sum('quantity_change');
    }

    /**
     * @param  string  $type  purchase, sale, return, adjust, damage
     * @param  int  $change  + into stock, - out of stock
     */
    public static function move(Product $product, string $type, int $change, array $extra = []): StockMovement
    {
        $onHand = self::lock($product);
        if ($onHand + $change < 0) {
            throw ValidationException::withMessages(['items' => "Only {$onHand} left of \"{$product->title}\"."]);
        }
        $by = $extra['by'] ?? null;

        return StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity_change' => $change,
            'handled_by' => $by instanceof User ? $by->id : $by,
            'order_item_id' => $extra['order_item_id'] ?? null,
            'purchase_order_item_id' => $extra['purchase_order_item_id'] ?? null,
            'reason' => $extra['reason'] ?? null,
        ]);
    }

    /** Staff typed a new shelf count: record the difference as an adjustment. */
    public static function setQuantity(Product $product, int $quantity, ?User $by = null, string $reason = 'Stock count updated by staff'): void
    {
        $current = self::lock($product);
        if ($quantity !== $current) {
            self::move($product, 'adjust', $quantity - $current, ['by' => $by, 'reason' => $reason]);
        }
    }

    /** Goods received from a supplier: the cost is on the purchase order item, the movement adds the units. */
    public static function receive(Product $product, int $quantity, ?User $by = null, ?int $purchaseOrderItemId = null): void
    {
        self::move($product, 'purchase', $quantity, [
            'by' => $by, 'purchase_order_item_id' => $purchaseOrderItemId, 'reason' => 'Received from supplier',
        ]);
    }
}
