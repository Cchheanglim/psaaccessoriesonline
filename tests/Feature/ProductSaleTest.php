<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Edit product > Put it on sale: percent off, optional end day; orders charge the sale price. */
class ProductSaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
    }

    private function product(): array
    {
        return collect($this->getJson('/api/bootstrap')->json('products'))->firstWhere('id', 'genz-01');
    }

    public function test_a_sale_changes_the_price_customers_pay_and_orders_keep_it(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'boss@example.com', 'password' => 'secret123', 'role' => 'admin']);
        $this->actingAs($admin)->patchJson('/api/admin/products/genz-01', ['discountPercent' => 95])->assertStatus(422);
        $this->actingAs($admin)->patchJson('/api/admin/products/genz-01', ['discountPercent' => 20, 'discountEndsAt' => now()->addDays(3)->toDateString()])
            ->assertOk()->assertJsonPath('product.priceUSD', 7.2)->assertJsonPath('product.basePriceUSD', 9)
            ->assertJsonPath('product.originalPriceUSD', 9)->assertJsonPath('product.onSale', true);

        $buyer = User::create(['name' => 'Dara', 'email' => 'dara@example.com', 'password' => 'secret123', 'role' => 'buyer']);
        $order = $this->actingAs($buyer)->postJson('/api/orders', [
            'customerName' => 'Dara Kim', 'phone' => '012 345 678', 'address' => 'St 63, Phnom Penh',
            'paymentMethod' => 'cod', 'items' => [['id' => 'genz-01', 'quantity' => 2]],
        ])->assertCreated()->json('order');
        $this->assertSame('14.40', $order['subtotalUSD']);
        $this->assertSame(7.2, $order['items'][0]['priceUSD']);

        // The sale ends after its last day; the old order keeps the price it was sold at
        $this->travel(4)->days();
        \Illuminate\Support\Facades\Cache::flush();
        $p = $this->product();
        $this->assertSame([9, null, false], [$p['priceUSD'], $p['originalPriceUSD'], $p['onSale']]);
        $this->assertSame('14.40', $this->actingAs($buyer)->getJson('/api/bootstrap')->json('orders.0.subtotalUSD'));

        // Turning the sale off clears its end day too
        $this->actingAs($admin)->patchJson('/api/admin/products/genz-01', ['discountPercent' => null])->assertOk();
        $this->assertNull(Product::where('sku', 'genz-01')->first()->discount_ends_at);
    }
}
