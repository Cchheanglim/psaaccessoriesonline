<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
    }

    private function makeUser(array $attributes = []): User
    {
        static $n = 0;
        $n++;

        return User::create($attributes + [
            'name' => "User {$n}",
            'email' => "user{$n}@example.com",
            'password' => 'secret123',
            'role' => 'buyer',
        ]);
    }

    private function orderPayload(array $overrides = []): array
    {
        return $overrides + [
            'customerName' => 'Test Buyer',
            'phone' => '+855 12 345 678',
            'address' => 'St 240, Phnom Penh',
            'paymentMethod' => 'khqr',
            'items' => [['id' => 'genz-01', 'quantity' => 2]],
        ];
    }

    public function test_bootstrap_returns_catalog_for_guests(): void
    {
        $this->getJson('/api/bootstrap')
            ->assertOk()
            ->assertJsonPath('user', null)
            ->assertJsonCount(12, 'products')
            ->assertJsonPath('products.0.id', 'genz-01')
            ->assertJsonPath('users', []);
    }

    public function test_register_then_login_with_email_or_phone(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Sophea Chhum', 'email' => 'Sophea@Example.com', 'phone' => '012345678', 'password' => 'secret123',
        ])->assertCreated()->assertJsonPath('user.role', 'Buyer');

        $this->postJson('/api/auth/logout')->assertOk();

        $this->postJson('/api/auth/login', ['login' => '012345678', 'password' => 'wrong'])->assertStatus(422);
        $this->postJson('/api/auth/login', ['login' => 'sophea@example.com', 'password' => 'secret123'])
            ->assertOk()->assertJsonPath('user.email', 'sophea@example.com');
    }

    public function test_order_uses_database_prices_and_decrements_stock(): void
    {
        $buyer = $this->makeUser(['role' => 'buyer']);
        $stockBefore = Product::where('sku', 'genz-01')->value('stock');

        $response = $this->actingAs($buyer)
            ->postJson('/api/orders', $this->orderPayload(['items' => [['id' => 'genz-01', 'quantity' => 2, 'priceUSD' => 0.01]]]))
            ->assertCreated()
            ->assertJsonPath('order.subtotalUSD', '13.00')
            ->assertJsonPath('order.deliveryUSD', '1.50')
            ->assertJsonPath('order.totalUSD', '14.50')
            ->assertJsonPath('order.status', 'Payment Pending');

        $this->assertSame($stockBefore - 2, Product::where('sku', 'genz-01')->value('stock'));

        $number = $response->json('order.id');
        $this->getJson('/api/bootstrap')->assertJsonPath('orders.0.id', $number);

        $this->postJson("/api/orders/{$number}/slip")->assertOk()->assertJsonPath('order.status', 'Slip Uploaded');
    }

    public function test_guest_can_order_and_only_see_their_own_orders(): void
    {
        $this->getJson('/api/bootstrap')->assertJsonCount(0, 'orders');

        $number = $this->postJson('/api/orders', $this->orderPayload())->assertCreated()->json('order.id');

        $this->getJson('/api/bootstrap')->assertJsonCount(1, 'orders')->assertJsonPath('orders.0.id', $number);
    }

    public function test_out_of_stock_and_disabled_payment_are_rejected(): void
    {
        Product::where('sku', 'genz-02')->update(['stock' => 1]);
        $this->postJson('/api/orders', $this->orderPayload(['items' => [['id' => 'genz-02', 'quantity' => 3]]]))
            ->assertStatus(422);

        \App\Models\PaymentMethod::where('code', 'cod')->update(['is_active' => false]);
        $this->postJson('/api/orders', $this->orderPayload(['paymentMethod' => 'cod']))->assertStatus(422);
    }

    public function test_admin_endpoints_enforce_roles(): void
    {
        $buyer = $this->makeUser(['role' => 'buyer']);
        $staff = $this->makeUser(['role' => 'staff']);
        $admin = $this->makeUser(['role' => 'admin']);

        $this->postJson('/api/admin/products', [])->assertStatus(401);
        $this->actingAs($buyer)->patchJson('/api/admin/products/genz-01', ['stock' => 5])->assertStatus(403);

        $this->actingAs($staff)->patchJson('/api/admin/products/genz-01', ['stock' => 5, 'priceUSD' => 7])
            ->assertOk()->assertJsonPath('product.stock', 5)->assertJsonPath('product.priceKHR', 28700);
        $this->actingAs($staff)->deleteJson('/api/admin/products/genz-01')->assertStatus(403);
        $this->actingAs($staff)->patchJson("/api/admin/users/{$buyer->id}", ['role' => 'Admin'])->assertStatus(403);

        $this->actingAs($admin)->patchJson("/api/admin/users/{$staff->id}", ['role' => 'Buyer'])
            ->assertOk()->assertJsonPath('user.role', 'Buyer');
        $this->actingAs($admin)->patchJson("/api/admin/users/{$admin->id}", ['role' => 'Buyer'])->assertStatus(422);
        $this->actingAs($admin)->deleteJson('/api/admin/products/genz-01')->assertOk();
    }

    public function test_staff_fulfils_orders_and_admin_cancel_restores_stock(): void
    {
        $staff = $this->makeUser(['role' => 'staff']);
        $admin = $this->makeUser(['role' => 'admin']);
        $number = $this->postJson('/api/orders', $this->orderPayload())->json('order.id');
        $stock = Product::where('sku', 'genz-01')->value('stock');

        $this->actingAs($staff)->patchJson("/api/admin/orders/{$number}", ['action' => 'verify'])
            ->assertOk()->assertJsonPath('order.status', 'Processing');
        $this->actingAs($staff)->patchJson("/api/admin/orders/{$number}", ['action' => 'cancel'])->assertStatus(403);

        $this->actingAs($admin)->patchJson("/api/admin/orders/{$number}", ['action' => 'cancel'])
            ->assertOk()->assertJsonPath('order.status', 'Cancelled');
        $this->assertSame($stock + 2, Product::where('sku', 'genz-01')->value('stock'));
    }

    public function test_suspended_users_cannot_log_in(): void
    {
        $this->makeUser(['email' => 'banned@example.com', 'password' => 'secret123', 'status' => 'Suspended']);

        $this->postJson('/api/auth/login', ['login' => 'banned@example.com', 'password' => 'secret123'])->assertStatus(422);
    }
}
