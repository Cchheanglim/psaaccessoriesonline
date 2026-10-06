<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The only place stock changes. Each change writes a stock_movements row and updates
 * product_stocks.quantity_on_hand in the same step, so the two never disagree.
 * Call inside a DB transaction when other rows change too.
 */
class Inventory
{
    /** Lock and return the product's stock row (creating it if a product has none yet). */
    public static function lock(Product $product): ProductStock
    {
        ProductStock::firstOrCreate(['product_id' => $product->id]);

        return ProductStock::where('product_id', $product->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  string  $type  purchase, sale, return, adjust, damage
     * @param  int  $change  + into stock, - out of stock
     */
    public static function move(Product $product, string $type, int $change, array $extra = []): StockMovement
    {
        $stock = self::lock($product);
        $after = $stock->quantity_on_hand + $change;
        if ($after < 0) {
            throw ValidationException::withMessages(['items' => "Only {$stock->quantity_on_hand} left of \"{$product->title}\"."]);
        }

        $stock->update(['quantity_on_hand' => $after]);
        $product->setRelation('stock', $stock);
        $by = $extra['by'] ?? null;

        return StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity_change' => $change,
            'handled_by' => $by instanceof User ? $by->id : $by,
            'unit_cost_usd' => $extra['unit_cost'] ?? $stock->average_cost_usd,
            'unit_price_usd' => $extra['unit_price'] ?? $product->price_usd,
            'order_item_id' => $extra['order_item_id'] ?? null,
            'purchase_order_item_id' => $extra['purchase_order_item_id'] ?? null,
            'reason' => $extra['reason'] ?? null,
        ]);
    }

    /** Staff typed a new shelf count: record the difference as an adjustment. */
    public static function setQuantity(Product $product, int $quantity, ?User $by = null, string $reason = 'Stock count updated by staff'): void
    {
        $current = self::lock($product)->quantity_on_hand;
        if ($quantity !== $current) {
            self::move($product, 'adjust', $quantity - $current, ['by' => $by, 'reason' => $reason]);
        }
    }

    /** Goods received from a supplier: add stock and update the average BUY cost. */
    public static function receive(Product $product, int $quantity, float $unitCost, ?User $by = null, ?int $purchaseOrderItemId = null): void
    {
        $stock = self::lock($product);
        $onHand = max(0, $stock->quantity_on_hand);
        $oldCost = $stock->average_cost_usd !== null ? (float) $stock->average_cost_usd : $unitCost;
        $average = ($onHand + $quantity) > 0 ? (($onHand * $oldCost) + ($quantity * $unitCost)) / ($onHand + $quantity) : $unitCost;
        $stock->update(['average_cost_usd' => round($average, 2)]);

        self::move($product, 'purchase', $quantity, [
            'by' => $by, 'unit_cost' => $unitCost, 'purchase_order_item_id' => $purchaseOrderItemId, 'reason' => 'Received from supplier',
        ]);
    }
}
