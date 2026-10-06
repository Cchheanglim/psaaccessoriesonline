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

/** The 2026-10 tables: customers, payments, status history, stock movements, loyalty, settings. */
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

        $this->assertSame($buyer->id, $order->customer->user_id);
        $this->assertSame('pending_payment', $order->order_status);
        $this->assertSame('bakong_khqr', $order->payment_method);
        $this->assertCount(1, $order->payments);
        $this->assertSame('pending', $order->payment_status);
        $this->assertEquals(18.00, (float) $order->payments->first()->amount_usd);
        $this->assertSame(['pending_payment'], $order->statusHistory->map(fn ($h) => $h->status->code)->all());

        $item = $order->items->first();
        $this->assertEquals(9.00, (float) $item->unit_price_usd);
        $sale = StockMovement::where('order_item_id', $item->id)->firstOrFail();
        $this->assertSame('sale', $sale->type);
        $this->assertSame(-2, $sale->quantity_change);
        $this->assertSame(48, $item->product->stock->quantity_on_hand);
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

        $codes = $order->fresh()->statusHistory->map(fn ($h) => $h->status->code)->all();
        $this->assertSame(['pending_payment', 'processing', 'out_for_delivery', 'delivered'], $codes);
        $this->assertSame($staff->id, $order->fresh()->latestPayment->verified_by);
    }

    public function test_delivered_orders_earn_loyalty_points_and_count_toward_total_spent(): void
    {
        $buyer = $this->user();
        $staff = $this->user('staff', 'Sokha Lim');
        $order = $this->order($buyer, [['id' => 'genz-01', 'quantity' => 3]]); // 3 x $9 = $27, free delivery

        $this->actingAs($staff)->patchJson("/api/admin/orders/{$order->order_number}", ['action' => 'verify'])->assertOk();
        $customer = $buyer->fresh()->customer;
        $this->assertSame(0, $customer->loyalty_points); // nothing until delivered

        $this->actingAs($staff)->patchJson("/api/admin/orders/{$order->order_number}", ['action' => 'deliver'])->assertOk();
        $customer->refresh();
        $this->assertSame(27, $customer->loyalty_points);
        $this->assertEquals(27.00, (float) $customer->total_spent_usd);
        $this->assertSame('earn', $customer->loyaltyTransactions()->first()->type);
        $this->assertSame('Bronze', $customer->tier->name);

        $this->actingAs($buyer->fresh())->getJson('/api/bootstrap')->assertJsonPath('user.loyalty.points', 27)->assertJsonPath('user.loyalty.tier', 'Bronze');
    }

    public function test_enough_points_move_a_customer_up_a_tier(): void
    {
        $customer = $this->user()->customerProfile();
        $customer->addPoints('adjust', 600, null, null, 'Welcome bonus');

        $this->assertSame('Silver', $customer->fresh()->tier->name);
        $this->assertSame(600, $customer->fresh()->loyalty_points);
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

    public function test_every_account_gets_settings_and_buyers_get_a_customer_profile(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Sophea Chhum', 'email' => 'sophea@example.com', 'phone' => '012345678', 'password' => 'secret123',
        ])->assertCreated()
            ->assertJsonPath('user.settings.theme', 'system')
            ->assertJsonPath('user.loyalty.points', 0);

        $user = User::where('email', 'sophea@example.com')->firstOrFail();
        $this->assertNotNull($user->settings);
        $this->assertNotNull($user->customer);
        $this->assertNotNull($this->user('staff', 'Sokha Lim')->settings);
        $this->assertNull(User::where('name', 'Sokha Lim')->first()->customer);

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

        $addresses = $buyer->fresh()->customer->addresses;
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
        $product = Product::where('sku', 'genz-01')->firstOrFail(); // 50 in stock, cost unknown
        Inventory::receive($product, 10, 4.00);
        $this->assertEquals(4.00, (float) $product->stock->fresh()->average_cost_usd); // unknown cost counts as the new price
        $this->assertSame(60, $product->stock->fresh()->quantity_on_hand);

        Inventory::receive($product, 60, 6.00);
        $this->assertEquals(5.00, (float) $product->stock->fresh()->average_cost_usd); // (60 x 4 + 60 x 6) / 120

        $item = $this->order($this->user())->items->first();
        $this->assertEquals(5.00, (float) $item->unit_cost_usd); // BUY cost saved with the sale
        $this->assertEquals(8.00, ((float) $item->unit_price_usd - (float) $item->unit_cost_usd) * $item->quantity); // profit
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
}
