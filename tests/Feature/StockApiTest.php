<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Drops & Stock > Suppliers and Buying stock. */
class StockApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
    }

    private function user(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create(['name' => "{$role} {$n}", 'email' => "{$role}{$n}@example.com", 'password' => 'secret123', 'role' => $role]);
    }

    public function test_staff_add_edit_and_admins_remove_suppliers(): void
    {
        $staff = $this->user('staff');
        $this->actingAs($this->user('buyer'))->postJson('/api/admin/suppliers', ['name' => 'X'])->assertStatus(403);

        $id = $this->actingAs($staff)->postJson('/api/admin/suppliers', ['name' => 'PP Wholesale', 'phone' => '012 345 678', 'email' => 'sales@pp.example'])
            ->assertCreated()->assertJsonPath('supplier.name', 'PP Wholesale')->json('supplier.id');
        $this->actingAs($staff)->patchJson("/api/admin/suppliers/{$id}", ['contactName' => 'Dara'])->assertOk()->assertJsonPath('supplier.contactName', 'Dara');
        $this->actingAs($staff)->postJson('/api/admin/suppliers', ['name' => 'Bad', 'phone' => 'call me'])->assertStatus(422);

        // only admins delete; a supplier with purchase orders is hidden instead
        $this->actingAs($staff)->deleteJson("/api/admin/suppliers/{$id}")->assertStatus(403);
        $admin = $this->user('admin');
        $this->actingAs($staff)->postJson('/api/admin/purchase-orders', ['supplierId' => $id, 'lines' => [['product' => 'genz-01', 'quantity' => 5, 'unitCost' => 3]]])->assertCreated();
        $this->actingAs($admin)->deleteJson("/api/admin/suppliers/{$id}")->assertOk()->assertJsonPath('archived', true);
        $this->assertFalse(Supplier::find($id)->is_active);

        $empty = Supplier::create(['name' => 'Unused', 'is_active' => true]);
        $this->actingAs($admin)->deleteJson("/api/admin/suppliers/{$empty->id}")->assertOk()->assertJsonPath('archived', false);
        $this->assertNull(Supplier::find($empty->id));
    }

    public function test_a_purchase_order_goes_from_draft_to_received_and_adds_stock(): void
    {
        $staff = $this->user('staff');
        $supplier = Supplier::create(['name' => 'PP Wholesale', 'is_active' => true]);
        $product = Product::where('sku', 'genz-01')->firstOrFail(); // 50 in stock

        // draft, then edit it
        $po = $this->actingAs($staff)->postJson('/api/admin/purchase-orders', ['supplierId' => $supplier->id, 'lines' => [['product' => 'genz-01', 'quantity' => 10, 'unitCost' => 4]]])
            ->assertCreated()->assertJsonPath('purchaseOrder.status', 'draft')->assertJsonPath('purchaseOrder.totalUSD', 40)->json('purchaseOrder');
        $this->actingAs($staff)->patchJson("/api/admin/purchase-orders/{$po['id']}", ['supplierId' => $supplier->id, 'lines' => [
            ['product' => 'genz-01', 'quantity' => 20, 'unitCost' => 4.5], ['product' => 'genz-02', 'quantity' => 5, 'unitCost' => 6],
        ]])->assertOk()->assertJsonPath('purchaseOrder.totalUSD', 120)->assertJsonCount(2, 'purchaseOrder.lines');

        // receiving a draft is not allowed; order it, then receive it
        $this->actingAs($staff)->patchJson("/api/admin/purchase-orders/{$po['id']}", ['action' => 'receive'])->assertStatus(422);
        $this->actingAs($staff)->patchJson("/api/admin/purchase-orders/{$po['id']}", ['action' => 'order'])->assertOk()->assertJsonPath('purchaseOrder.status', 'ordered');
        $this->actingAs($staff)->patchJson("/api/admin/purchase-orders/{$po['id']}", ['supplierId' => $supplier->id, 'lines' => [['product' => 'genz-01', 'quantity' => 1, 'unitCost' => 1]]])->assertStatus(422); // not a draft
        $this->actingAs($staff)->patchJson("/api/admin/purchase-orders/{$po['id']}", ['action' => 'receive'])
            ->assertOk()->assertJsonPath('purchaseOrder.status', 'received')
            ->assertJsonPath('products.0.stock', 70);

        $this->assertSame(70, $product->fresh()->stock_on_hand);
        $this->assertEquals(4.5, $product->averageCost());
        $this->assertSame('purchase', $product->stockMovements()->latest('id')->value('type'));

        // a received order can't be cancelled or deleted
        $this->actingAs($staff)->patchJson("/api/admin/purchase-orders/{$po['id']}", ['action' => 'cancel'])->assertStatus(422);
        $this->actingAs($staff)->deleteJson("/api/admin/purchase-orders/{$po['id']}")->assertStatus(422);
    }

    public function test_drafts_can_be_deleted_and_orders_cancelled(): void
    {
        $staff = $this->user('staff');
        $supplier = Supplier::create(['name' => 'PP Wholesale', 'is_active' => true]);
        $line = [['product' => 'genz-01', 'quantity' => 2, 'unitCost' => 3]];

        $draft = $this->actingAs($staff)->postJson('/api/admin/purchase-orders', ['supplierId' => $supplier->id, 'lines' => $line])->json('purchaseOrder.id');
        $this->actingAs($staff)->deleteJson("/api/admin/purchase-orders/{$draft}")->assertOk();
        $this->assertNull(PurchaseOrder::find($draft));

        $placed = $this->actingAs($staff)->postJson('/api/admin/purchase-orders', ['supplierId' => $supplier->id, 'lines' => $line, 'place' => true])
            ->assertJsonPath('purchaseOrder.status', 'ordered')->json('purchaseOrder.id');
        $this->actingAs($staff)->patchJson("/api/admin/purchase-orders/{$placed}", ['action' => 'cancel'])->assertOk()->assertJsonPath('purchaseOrder.status', 'cancelled');
        $this->assertSame(50, Product::where('sku', 'genz-01')->firstOrFail()->stock_on_hand); // cancelled: no stock added

        // the same product twice, or no lines, is refused
        $this->actingAs($staff)->postJson('/api/admin/purchase-orders', ['supplierId' => $supplier->id, 'lines' => [$line[0], $line[0]]])->assertStatus(422);
        $this->actingAs($staff)->postJson('/api/admin/purchase-orders', ['supplierId' => $supplier->id, 'lines' => []])->assertStatus(422);
    }
}
