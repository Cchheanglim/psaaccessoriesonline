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
            ->assertJsonCount(32, 'products')
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
            ->postJson('/api/orders', $this->orderPayload(['items' => [['id' => 'genz-01', 'quantity' => 1, 'priceUSD' => 0.01]]]))
            ->assertCreated()
            ->assertJsonPath('order.subtotalUSD', '9.00')
            ->assertJsonPath('order.deliveryUSD', '1.50')
            ->assertJsonPath('order.totalUSD', '10.50')
            ->assertJsonPath('order.status', 'Payment Pending');

        $this->assertSame($stockBefore - 1, Product::where('sku', 'genz-01')->value('stock'));

        $number = $response->json('order.id');
        $this->getJson('/api/bootstrap')->assertJsonPath('orders.0.id', $number);

        $this->postJson("/api/orders/{$number}/slip")->assertOk()->assertJsonPath('order.status', 'Slip Uploaded');
    }

    public function test_guests_cannot_order(): void
    {
        $this->postJson('/api/orders', $this->orderPayload())->assertStatus(401);
    }

    public function test_buyers_only_see_their_own_orders(): void
    {
        $buyer = $this->makeUser();
        $other = $this->makeUser();

        $number = $this->actingAs($buyer)->postJson('/api/orders', $this->orderPayload())->assertCreated()->json('order.id');

        $this->actingAs($buyer)->getJson('/api/bootstrap')->assertJsonCount(1, 'orders')->assertJsonPath('orders.0.id', $number);
        $this->actingAs($other)->getJson('/api/bootstrap')->assertJsonCount(0, 'orders');
    }

    public function test_out_of_stock_and_disabled_payment_are_rejected(): void
    {
        $buyer = $this->makeUser();

        Product::where('sku', 'genz-02')->update(['stock' => 1]);
        $this->actingAs($buyer)->postJson('/api/orders', $this->orderPayload(['items' => [['id' => 'genz-02', 'quantity' => 3]]]))
            ->assertStatus(422);

        \App\Models\PaymentMethod::where('code', 'cod')->update(['is_active' => false]);
        $this->actingAs($buyer)->postJson('/api/orders', $this->orderPayload(['paymentMethod' => 'cod']))->assertStatus(422);
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
        $number = $this->actingAs($this->makeUser())->postJson('/api/orders', $this->orderPayload())->json('order.id');
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

    public function test_profile_photo_can_be_saved_on_its_own(): void
    {
        $user = $this->makeUser();
        $photo = 'data:image/png;base64,iVBORw0KGgo=';

        // dashboard-buyer.html sends only the photo, so no other field may be required.
        $this->actingAs($user)->patchJson('/api/me', ['photo' => $photo])
            ->assertOk()
            ->assertJsonPath('user.avatarUrl', $photo);
    }

    public function test_profile_banner_can_be_set_and_removed(): void
    {
        $user = $this->makeUser();
        $banner = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ==';

        $this->actingAs($user)->patchJson('/api/me', ['banner' => $banner])
            ->assertOk()->assertJsonPath('user.bannerUrl', $banner);
        $this->actingAs($user)->patchJson('/api/me', ['banner' => null])
            ->assertOk()->assertJsonPath('user.bannerUrl', null);
        $this->actingAs($user)->patchJson('/api/me', ['banner' => 'javascript:alert(1)'])->assertStatus(422);
    }

    public function test_profile_and_payment_method_text_fits_the_database(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->patchJson('/api/me', ['address' => str_repeat('a', 256)])
            ->assertStatus(422)->assertJsonValidationErrors('address');
        $this->actingAs($user)->patchJson('/api/me', ['phone' => '@telegram_name'])
            ->assertStatus(422)->assertJsonValidationErrors('phone');

        $admin = $this->makeUser(['role' => 'admin']);
        $this->actingAs($admin)->postJson('/api/admin/payment-methods', ['name' => 'Wing', 'description' => str_repeat('a', 256)])
            ->assertStatus(422)->assertJsonValidationErrors('description');
    }

    public function test_admin_can_edit_a_user_without_changing_their_password(): void
    {
        $admin = $this->makeUser(['role' => 'admin']);
        $buyer = $this->makeUser();

        $this->actingAs($admin)->patchJson("/api/admin/users/{$buyer->id}", ['role' => 'Staff', 'password' => null])
            ->assertOk();
        $this->assertSame('staff', $buyer->fresh()->role);
        $this->postJson('/api/auth/logout');
        $this->postJson('/api/auth/login', ['login' => $buyer->email, 'password' => 'secret123'])->assertOk();
    }

    public function test_orders_can_use_a_payment_method_an_admin_created(): void
    {
        $admin = $this->makeUser(['role' => 'admin']);
        $code = $this->actingAs($admin)->postJson('/api/admin/payment-methods', [
            'name' => 'Wing Bank', 'type' => 'khqr', 'qrData' => 'data:image/png;base64,iVBORw0KGgo=',
        ])->assertCreated()->json('paymentMethod.code');

        $this->actingAs($this->makeUser())->postJson('/api/orders', $this->orderPayload(['paymentMethod' => $code]))
            ->assertCreated()
            ->assertJsonPath('order.paymentCode', $code)
            ->assertJsonPath('order.paymentMethod', 'Wing Bank');

        $this->actingAs($this->makeUser())->postJson('/api/orders', $this->orderPayload(['paymentMethod' => 'no-such-method']))
            ->assertStatus(422)->assertJsonValidationErrors('paymentMethod');
    }

    public function test_customer_and_staff_can_message_about_an_order(): void
    {
        $buyer = $this->makeUser();
        $staff = $this->makeUser(['role' => 'staff']);
        $number = $this->actingAs($buyer)->postJson('/api/orders', $this->orderPayload())->json('order.id');

        $this->actingAs($buyer)->postJson("/api/orders/{$number}/messages", ['body' => 'Can you deliver after 5pm?'])
            ->assertCreated()->assertJsonPath('messages.0.fromStaff', false);
        $this->actingAs($staff)->postJson("/api/orders/{$number}/messages", ['body' => 'Yes, no problem!'])
            ->assertCreated()
            ->assertJsonPath('messages.1.fromStaff', true)
            ->assertJsonPath('messages.1.author', 'PsaOnline');

        $this->actingAs($buyer)->getJson("/api/orders/{$number}/messages")->assertOk()->assertJsonCount(2, 'messages');
        $this->actingAs($this->makeUser())->getJson("/api/orders/{$number}/messages")->assertStatus(403);
        $this->actingAs($buyer)->postJson("/api/orders/{$number}/messages", ['body' => ''])->assertStatus(422);
    }

    public function test_customers_review_items_after_delivery(): void
    {
        $buyer = $this->makeUser(['name' => 'Sophea Chhum']);
        $staff = $this->makeUser(['role' => 'staff']);
        $number = $this->actingAs($buyer)->postJson('/api/orders', $this->orderPayload())->json('order.id');
        $review = ['reviews' => [['id' => 'genz-01', 'rating' => 4, 'comment' => 'So comfy']]];

        $this->actingAs($buyer)->postJson("/api/orders/{$number}/reviews", $review)->assertStatus(422);

        $this->actingAs($staff)->patchJson("/api/admin/orders/{$number}", ['action' => 'verify'])->assertOk();
        $this->actingAs($staff)->patchJson("/api/admin/orders/{$number}", ['action' => 'deliver'])->assertOk();

        $this->actingAs($buyer)->postJson("/api/orders/{$number}/reviews", $review)
            ->assertOk()->assertJsonPath('order.reviewed', true);
        $this->actingAs($buyer)->postJson("/api/orders/{$number}/reviews", ['reviews' => [['id' => 'genz-02', 'rating' => 5]]])
            ->assertStatus(422);

        $this->getJson('/api/products/genz-01/reviews')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('reviews.0.author', 'Sophea C.')
            ->assertJsonPath('reviews.0.comment', 'So comfy');
        $this->assertEquals(4.0, (float) Product::where('sku', 'genz-01')->value('rating'));
    }

    public function test_legal_pages_redirect_to_their_html_files(): void
    {
        $this->get('/about')->assertRedirect('/about.html');
        $this->get('/privacy')->assertRedirect('/privacy.html');
        $this->get('/terms')->assertRedirect('/terms.html');
    }
}
