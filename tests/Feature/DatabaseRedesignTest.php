<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\Inventory;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The 2026-10 tables: customers, payments, status history, stock movements, loyalty. */
class DatabaseRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
    }

    private function user(string $role = 'buyer', string $name = 'Dara Sok'): User
    {
        static $n = 0;
        $n++;

        return User::create(['name' => $name, 'email' => "person{$n}@example.com", 'password' => 'secret123', 'role' => $role]);
    }

    private function order(User $buyer, array $items = [['id' => 'genz-01', 'quantity' => 2]], string $payment = 'khqr'): Order
    {
        $number = $this->actingAs($buyer)->postJson('/api/orders', [
            'customerName' => $buyer->name, 'phone' => '012 345 678', 'address' => 'St 240, Phnom Penh',
            'paymentMethod' => $payment, 'items' => $items,
        ])->assertCreated()->json('order.id');

        return Order::where('order_number', $number)->firstOrFail();
    }

    public function test_an_order_records_its_customer_payment_history_and_stock_movement(): void
    {
        $buyer = $this->user();
        $order = $this->order($buyer);

        $this->assertSame($buyer->id, $order->user_id);
        $this->assertSame('pending_payment', $order->order_status);
        $this->assertSame('bakong_khqr', $order->payment_method);
        $this->assertCount(1, $order->payments);
        $this->assertSame('pending', $order->payment_status);
        $this->assertEquals(18.00, (float) $order->payments->first()->amount_usd);
        $this->assertSame(['pending_payment'], $order->statusHistory->map(fn ($h) => $h->status)->all());

        $item = $order->items->first();
        $this->assertEquals(9.00, (float) $item->unit_price_usd);
        $sale = StockMovement::where('order_item_id', $item->id)->firstOrFail();
        $this->assertSame('sale', $sale->type);
        $this->assertSame(-2, $sale->quantity_change);
        $this->assertSame(48, $item->product->stock_on_hand); // calculated: 50 opening − 2 sold
    }

    public function test_staff_can_only_take_real_steps(): void
    {
        $staff = $this->user('staff', 'Sokha Lim');
        $admin = $this->user('admin', 'Vanna Chea');
        $order = $this->order($this->user());
        $url = "/api/admin/orders/{$order->order_number}";

        $this->actingAs($staff)->patchJson($url, ['action' => 'deliver'])->assertStatus(422);
        $this->actingAs($staff)->patchJson($url, ['action' => 'dispatch'])->assertStatus(422);
        $this->actingAs($staff)->patchJson($url, ['action' => 'verify'])->assertOk()->assertJsonPath('order.orderStatus', 'processing');
        $this->actingAs($staff)->patchJson($url, ['action' => 'verify'])->assertStatus(422);
        $this->actingAs($staff)->patchJson($url, ['action' => 'dispatch'])->assertOk();
        $this->actingAs($staff)->patchJson($url, ['action' => 'deliver'])->assertOk()->assertJsonPath('order.status', 'Delivered');
        $this->actingAs($admin)->patchJson($url, ['action' => 'cancel'])->assertStatus(422); // already delivered

        $codes = $order->fresh()->statusHistory->map(fn ($h) => $h->status)->all();
        $this->assertSame(['pending_payment', 'processing', 'out_for_delivery', 'delivered'], $codes);
        $this->assertSame($staff->id, $order->fresh()->latestPayment->verified_by);
    }

    public function test_only_delivered_orders_count_toward_total_spent(): void
    {
        $buyer = $this->user();
        $staff = $this->user('staff', 'Sokha Lim');
        $order = $this->order($buyer, [['id' => 'genz-01', 'quantity' => 3]]); // 3 x $9 = $27, free delivery

        $this->actingAs($staff)->patchJson("/api/admin/orders/{$order->order_number}", ['action' => 'verify'])->assertOk();
        $this->assertEquals(0.0, (float) $buyer->fresh()->total_spent_usd); // nothing until delivered

        $this->actingAs($staff)->patchJson("/api/admin/orders/{$order->order_number}", ['action' => 'deliver'])->assertOk();
        $customer = $buyer->fresh();
        $this->assertEquals(27.00, (float) $customer->total_spent_usd);
        $this->assertSame('Plus', $customer->tier->name);

        $this->actingAs($buyer->fresh())->getJson('/api/bootstrap')->assertJsonPath('user.loyalty.spendUSD', '27.00')->assertJsonPath('user.loyalty.tier', 'Plus');
    }

    public function test_a_new_slip_after_a_rejected_one_is_a_new_payment_attempt(): void
    {
        $buyer = $this->user();
        $staff = $this->user('staff', 'Sokha Lim');
        $order = $this->order($buyer);
        $slip = 'data:image/png;base64,iVBORw0KGgo=';

        $this->actingAs($buyer)->postJson("/api/orders/{$order->order_number}/slip", ['slip' => $slip])->assertOk()
            ->assertJsonPath('order.slipImage', $slip)->assertJsonPath('order.status', 'Slip Uploaded');
        $this->actingAs($staff)->patchJson("/api/admin/orders/{$order->order_number}", ['action' => 'reject'])->assertOk()
            ->assertJsonPath('order.status', 'Payment Failed');
        $this->actingAs($buyer)->postJson("/api/orders/{$order->order_number}/slip", ['slip' => $slip])->assertOk()
            ->assertJsonPath('order.status', 'Slip Uploaded');

        $this->assertSame(['failed', 'slip_uploaded'], $order->fresh()->payments->pluck('status')->all());
    }

    public function test_a_customer_is_a_user_and_staff_get_no_membership(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Sophea Chhum', 'email' => 'sophea@example.com', 'phone' => '012345678', 'password' => 'secret123',
        ])->assertCreated()
            ->assertJsonPath('user.loyalty.tier', 'Plus')
            ->assertJsonPath('user.loyalty.nextTier', 'Pro')
            ->assertJsonPath('user.loyalty.spendToNextUSD', '100.00')
            ->assertJsonPath('user.loyalty.discountPercent', 0);

        // A customer is a user (1:1 customers row); staff get no customer row and no membership
        $customers = \App\Models\Customer::count();
        $this->user('staff', 'Sokha Lim');
        $this->assertSame($customers, \App\Models\Customer::count());
        $this->assertNull(\App\Support\Storefront::user(User::where('name', 'Sokha Lim')->first())['loyalty']);

        // Same phone number cannot be used twice (login works by phone)
        $this->postJson('/api/auth/logout');
        $this->postJson('/api/auth/register', [
            'name' => 'Other Person', 'email' => 'other@example.com', 'phone' => '012345678', 'password' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_profile_address_is_the_customers_default_saved_address(): void
    {
        $buyer = $this->user();

        $this->actingAs($buyer)->patchJson('/api/me', ['address' => 'St 271, Toul Tompoung'])->assertOk()
            ->assertJsonPath('user.address', 'St 271, Toul Tompoung');
        $this->actingAs($buyer)->patchJson('/api/me', ['address' => 'St 63, BKK1'])->assertOk();

        $addresses = $buyer->fresh()->addresses;
        $this->assertCount(1, $addresses);
        $this->assertTrue($addresses->first()->is_default);
        $this->assertSame('St 63, BKK1', $addresses->first()->address_line);
    }

    public function test_staff_product_edits_fill_details_pictures_category_and_stock(): void
    {
        $staff = $this->user('staff', 'Sokha Lim');
        $sku = $this->actingAs($staff)->postJson('/api/admin/products', [
            'title' => 'Denim Bucket Hat', 'category' => 'hats', 'categoryLabel' => 'Bucket Hats', 'priceUSD' => 8.5, 'stock' => 12,
            'description' => 'Soft washed denim.', 'specifications' => ['Size' => 'One size'],
            'gallery' => ['/assets/images/products/a.jpg', '/assets/images/products/b.jpg'],
        ])->assertCreated()
            ->assertJsonPath('product.category', 'hats')
            ->assertJsonPath('product.categoryLabel', 'Bucket Hats')
            ->assertJsonPath('product.stock', 12)
            ->assertJsonMissingPath('product.priceKHR')
            ->assertJsonPath('product.image', '/assets/images/products/a.jpg')
            ->assertJsonPath('product.description', 'Soft washed denim.')
            ->json('product.id');

        $product = Product::where('sku', $sku)->firstOrFail();
        $this->assertSame('hats', $product->category->parent->slug); // "Bucket Hats" is a sub-category of Caps & Hats
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());

        $this->actingAs($staff)->patchJson("/api/admin/products/{$sku}", ['stock' => 7])->assertOk()->assertJsonPath('product.stock', 7);
        $this->assertSame([12, -5], $product->stockMovements()->orderBy('id')->pluck('quantity_change')->all());
        $this->assertSame('adjust', $product->stockMovements()->orderByDesc('id')->value('type'));
    }

    public function test_deleting_a_product_that_was_ordered_archives_it_instead(): void
    {
        $admin = $this->user('admin', 'Vanna Chea');
        $this->order($this->user());

        $this->actingAs($admin)->deleteJson('/api/admin/products/genz-01')->assertOk()->assertJsonPath('archived', true);
        $this->assertSame('archived', Product::where('sku', 'genz-01')->value('status'));

        $this->actingAs($admin)->deleteJson('/api/admin/products/genz-02')->assertOk()->assertJsonPath('archived', false);
        $this->assertNull(Product::where('sku', 'genz-02')->first());
    }

    public function test_buying_stock_from_a_supplier_updates_the_average_cost_and_the_profit_data(): void
    {
        $product = Product::where('sku', 'genz-01')->firstOrFail(); // 50 in stock, no purchases yet
        $this->assertNull($product->averageCost());
        $supplier = \App\Models\Supplier::create(['name' => 'Phnom Penh Wholesale']);
        $staff = $this->user('staff', 'Sokha Lim');
        $buy = function (int $qty, float $cost) use ($product, $supplier, $staff) {
            $po = \App\Models\PurchaseOrder::create(['po_number' => 'PO-'.uniqid(), 'supplier_id' => $supplier->id, 'ordered_by' => $staff->id, 'status' => 'received', 'ordered_at' => now(), 'received_at' => now()]);
            $line = $po->items()->create(['product_id' => $product->id, 'quantity' => $qty, 'unit_cost_usd' => $cost]);
            Inventory::receive($product, $qty, $staff, $line->id);

            return $po;
        };

        $first = $buy(10, 4.00);
        $this->assertEquals(4.00, $product->averageCost());
        $this->assertSame(60, $product->stock_on_hand);
        $this->assertEquals(40.00, $first->totalCost()); // calculated from its items, not stored

        $buy(60, 6.00);
        $this->assertEquals(5.71, $product->averageCost()); // (10 x 4 + 60 x 6) / 70, from the purchase order items
        $this->assertSame(120, $product->stock_on_hand);

        $item = $this->order($this->user())->items->first();
        $this->assertEquals(5.71, (float) $item->unit_cost_usd); // BUY cost at the time, kept with the sale
        $this->assertEquals(6.58, round(((float) $item->unit_price_usd - (float) $item->unit_cost_usd) * $item->quantity, 2)); // profit
    }

    public function test_messages_know_who_wrote_them_without_a_from_staff_column(): void
    {
        $buyer = $this->user();
        $staff = $this->user('staff', 'Sokha Lim');
        $order = $this->order($buyer);

        $this->actingAs($buyer)->postJson("/api/orders/{$order->order_number}/messages", ['body' => 'Hi!'])->assertCreated();
        $this->actingAs($staff)->postJson("/api/orders/{$order->order_number}/messages", ['body' => 'Hello!'])
            ->assertCreated()
            ->assertJsonPath('messages.0.fromStaff', false)
            ->assertJsonPath('messages.1.fromStaff', true)
            ->assertJsonPath('messages.1.staffName', 'Sokha Lim');
    }

    public function test_categories_have_sub_categories(): void
    {
        $apparel = Category::where('slug', 'apparel')->firstOrFail();

        $this->assertNull($apparel->parent_id);
        $this->assertTrue($apparel->children()->exists());
        $this->assertSame(14, Category::whereNull('parent_id')->count());
    }

    public function test_3nf_orders_store_only_their_own_facts(): void
    {
        $buyer = $this->user();
        $staff = $this->user('staff', 'Sokha Lim');
        $order = $this->order($buyer, [['id' => 'genz-01', 'quantity' => 1]]); // $9 + $1.50 delivery (under $15)

        // totals, status, method and handler are calculated, not stored
        $this->assertEquals(9.00, $order->subtotal_usd);
        $this->assertEquals(10.50, $order->total_usd);
        $this->assertSame('pending_payment', $order->order_status);
        $this->assertSame('bakong_khqr', $order->payment_method);
        $this->assertNull($order->handled_by);

        $this->actingAs($staff)->patchJson("/api/admin/orders/{$order->order_number}", ['action' => 'verify'])
            ->assertOk()->assertJsonPath('order.orderStatus', 'processing')->assertJsonPath('order.handledBy', 'Sokha Lim');
        $order = $order->fresh();
        $this->assertSame('processing', $order->order_status);
        $this->assertSame($staff->id, $order->handled_by); // first change after "placed"

        // the same delivery details reuse one address; different ones get a new address
        $again = $this->order($buyer, [['id' => 'genz-02', 'quantity' => 1]]);
        $this->assertSame($order->address_id, $again->address_id);
        $this->assertSame(1, $buyer->addresses()->count());
    }

    public function test_an_address_an_order_used_is_never_edited(): void
    {
        $buyer = $this->user();
        $order = $this->order($buyer);
        $oldAddressId = $order->address_id;

        // editing the profile address saves a new one and archives the old one
        $this->actingAs($buyer)->patchJson('/api/me', ['address' => 'BKK 1, St 51, Phnom Penh'])->assertOk()
            ->assertJsonPath('user.address', 'BKK 1, St 51, Phnom Penh');
        $order = $order->fresh();
        $this->assertSame($oldAddressId, $order->address_id);
        $this->assertSame('St 240, Phnom Penh', $order->delivery_address); // the order still shows where it went
        $this->assertNotNull(\App\Models\CustomerAddress::find($oldAddressId)->archived_at);
        $this->assertSame('BKK 1, St 51, Phnom Penh', $buyer->fresh()->defaultAddress->address_line);
    }

    public function test_specifications_are_one_row_per_fact(): void
    {
        $staff = $this->user('staff', 'Sokha Lim');
        $this->actingAs($staff)->patchJson('/api/admin/products/genz-01', ['specifications' => ['Material' => 'Steel', 'Size' => '3 cm', 'Empty' => '']])
            ->assertOk()->assertJsonPath('product.specifications', ['Material' => 'Steel', 'Size' => '3 cm']);
        $product = Product::where('sku', 'genz-01')->firstOrFail();
        $this->assertSame(['Material', 'Size'], $product->specifications()->pluck('name')->all());
    }
}
