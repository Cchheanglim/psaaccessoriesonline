<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------
    // Admin area access
    // ---------------------------------------------------------------------

    public static function adminPages(): array
    {
        return [
            'dashboard' => ['/admin'],
            'orders' => ['/admin/orders'],
            'products' => ['/admin/products'],
            'product create' => ['/admin/products/create'],
            'users' => ['/admin/users'],
            'payment methods' => ['/admin/payment-methods'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_guests_are_sent_to_login_from_admin_pages(string $url): void
    {
        $this->get($url)->assertRedirect('/login');
    }

    #[DataProvider('adminPages')]
    public function test_buyers_are_forbidden_from_admin_pages(string $url): void
    {
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    #[DataProvider('adminPages')]
    public function test_admins_can_open_every_admin_page(string $url): void
    {
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
    }

    public function test_staff_can_operate_orders_and_products_but_not_admin_only_areas(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/admin/orders')->assertOk();
        $this->actingAs($staff)->get('/admin/products')->assertOk();
        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
        $this->actingAs($staff)->get('/admin/payment-methods')->assertForbidden();

        $product = Product::factory()->create();
        $this->actingAs($staff)->delete("/admin/products/{$product->id}")->assertForbidden();
        $this->assertModelExists($product);
    }

    public function test_a_guest_cannot_create_an_admin_account(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post('/admin/users', [
                'name' => 'Intruder',
                'email' => 'intruder@example.test',
                'role' => 'admin',
                'password' => 'Password12345',
            ])->assertRedirect('/login');

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.test']);
    }

    public function test_admin_can_create_staff_with_a_hashed_password(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/admin/users', [
            'name' => 'New Staff',
            'email' => 'staff@example.test',
            'role' => 'staff',
            'password' => 'Password12345',
        ])->assertSessionHasNoErrors();

        $created = User::where('email', 'staff@example.test')->firstOrFail();
        $this->assertSame('staff', $created->role);
        $this->assertNotSame('Password12345', $created->password);
        $this->assertTrue(Hash::check('Password12345', $created->password));
    }

    // ---------------------------------------------------------------------
    // Registration and login
    // ---------------------------------------------------------------------

    public function test_registration_ignores_a_submitted_role(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.test',
            'password' => 'Password12345',
            'password_confirmation' => 'Password12345',
            'role' => 'admin',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'sneaky@example.test')->firstOrFail();
        $this->assertSame('buyer', $user->role);
        $this->assertTrue(Hash::check('Password12345', $user->password));
    }

    public function test_registration_rejects_weak_passwords(): void
    {
        $this->post('/register', [
            'name' => 'Weak',
            'email' => 'weak@example.test',
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.test']);
    }

    public function test_a_password_that_looks_like_a_hash_is_still_hashed(): void
    {
        $hashLookingPassword = Hash::make('something-else');

        $this->post('/register', [
            'name' => 'Edge',
            'email' => 'edge@example.test',
            'password' => $hashLookingPassword.'a1',
            'password_confirmation' => $hashLookingPassword.'a1',
        ]);

        $user = User::where('email', 'edge@example.test')->firstOrFail();
        $this->assertNotSame($hashLookingPassword.'a1', $user->password);
        $this->assertTrue(Hash::check($hashLookingPassword.'a1', $user->password));
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(429);
    }

    public function test_login_regenerates_the_session_and_routes_staff_to_admin(): void
    {
        $staff = User::factory()->staff()->create();

        $this->post('/login', ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($staff);
    }

    public function test_buyer_dashboard_requires_login_and_only_lists_own_orders(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $buyer = User::factory()->create();
        $mine = Order::factory()->create(['user_id' => $buyer->id]);
        $theirs = Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($buyer)->get('/dashboard')
            ->assertOk()
            ->assertSee($mine->order_number)
            ->assertDontSee($theirs->order_number);
    }

    // ---------------------------------------------------------------------
    // Order ownership (IDOR)
    // ---------------------------------------------------------------------

    public function test_strangers_cannot_view_someone_elses_order(): void
    {
        $order = Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->get("/orders/{$order->id}")->assertForbidden();
        $this->get("/orders/{$order->id}/pending")->assertForbidden();
        $this->actingAs(User::factory()->create())->get("/orders/{$order->id}")->assertForbidden();
    }

    public function test_owner_and_staff_can_view_an_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->get("/orders/{$order->id}")->assertOk();
        $this->actingAs(User::factory()->staff()->create())->get("/orders/{$order->id}")->assertOk();
    }

    public function test_guest_checkout_can_view_its_own_order_but_not_others(): void
    {
        $product = Product::factory()->create(['price_usd' => 10.00, 'price_khr' => 41000]);
        $other = Order::factory()->create();

        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('count', 2);

        $response = $this->post('/checkout', [
            'customer_name' => 'Guest Buyer',
            'customer_phone' => '+855 12 345 678',
            'delivery_address' => 'Street 2, Phnom Penh',
            'payment_method' => 'bakong_khqr',
        ]);

        $order = Order::where('customer_name', 'Guest Buyer')->firstOrFail();
        $response->assertRedirect(route('orders.pending', $order));

        $this->assertEquals('21.50', $order->total_usd);
        $this->assertSame(2, $order->items()->sum('quantity'));

        $this->get("/orders/{$order->id}")->assertOk();
        $this->get("/orders/{$other->id}")->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // Payment slips
    // ---------------------------------------------------------------------

    public function test_slips_are_stored_privately_and_only_shown_to_authorised_users(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->post("/orders/{$order->id}/slip", ['payment_slip' => $this->realPng()])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame('slip_uploaded', $order->payment_status);
        Storage::disk('local')->assertExists($order->payment_slip_url);
        $this->assertEmpty(Storage::disk('public')->allFiles());

        $this->actingAs($owner)->get("/orders/{$order->id}/slip")->assertOk();
        $this->actingAs(User::factory()->staff()->create())->get("/orders/{$order->id}/slip")->assertOk();
        $this->actingAs(User::factory()->create())->get("/orders/{$order->id}/slip")->assertForbidden();
    }

    public function test_slip_upload_rejects_non_images_and_strangers(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->post("/orders/{$order->id}/slip", ['payment_slip' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php')])
            ->assertSessionHasErrors('payment_slip');

        $this->actingAs($owner)
            ->post("/orders/{$order->id}/slip", ['payment_slip' => UploadedFile::fake()->create('vector.svg', 1, 'image/svg+xml')])
            ->assertSessionHasErrors('payment_slip');

        $this->actingAs(User::factory()->create())
            ->post("/orders/{$order->id}/slip", ['payment_slip' => $this->realPng()])
            ->assertForbidden();

        $this->assertNull($order->fresh()->payment_slip_url);
    }

    public function test_slips_cannot_be_replaced_after_payment_is_verified(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id, 'payment_status' => 'verified']);

        $this->actingAs($owner)
            ->post("/orders/{$order->id}/slip", ['payment_slip' => $this->realPng()])
            ->assertForbidden();
    }

    /**
     * A genuine PNG, so MIME detection runs for real. (UploadedFile::fake()
     * ->image() would need the GD extension just to draw a test image.)
     */
    private function realPng(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('slip.png', file_get_contents(public_path('apple-touch-icon.png')));
    }

    // ---------------------------------------------------------------------
    // Order workflow permissions
    // ---------------------------------------------------------------------

    public function test_verifying_payment_updates_the_order_being_reviewed(): void
    {
        $first = Order::factory()->create(['payment_status' => 'slip_uploaded']);
        $second = Order::factory()->create(['payment_status' => 'slip_uploaded']);

        $this->actingAs(User::factory()->staff()->create())
            ->post("/admin/orders/{$second->id}/verify")
            ->assertRedirect(route('admin.orders.index'));

        $this->assertSame('verified', $second->fresh()->payment_status);
        $this->assertSame('slip_uploaded', $first->fresh()->payment_status);
    }

    public function test_only_admins_can_cancel_or_reopen_cancelled_orders(): void
    {
        $order = Order::factory()->create();
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($staff)->patch("/admin/orders/{$order->id}/status", ['order_status' => 'cancelled'])->assertForbidden();
        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", ['order_status' => 'cancelled'])->assertRedirect();
        $this->assertSame('cancelled', $order->fresh()->order_status);

        $this->actingAs($staff)->patch("/admin/orders/{$order->id}/status", ['order_status' => 'processing'])->assertForbidden();
        $this->assertSame('cancelled', $order->fresh()->order_status);
    }

    // ---------------------------------------------------------------------
    // Cart and catalogue
    // ---------------------------------------------------------------------

    public function test_cart_refuses_inactive_and_out_of_stock_products(): void
    {
        $draft = Product::factory()->create(['status' => 'draft']);
        $soldOut = Product::factory()->create(['stock' => 0]);

        $this->postJson('/cart/add', ['product_id' => $draft->id])->assertNotFound();
        $this->postJson('/cart/add', ['product_id' => $soldOut->id])->assertStatus(422);
        $this->postJson('/cart/add', ['product_id' => Product::factory()->create()->id, 'quantity' => 500])->assertStatus(422);
    }

    public function test_draft_products_are_not_publicly_visible(): void
    {
        $draft = Product::factory()->create(['status' => 'draft']);

        $this->get("/products/{$draft->id}")->assertNotFound();
    }

    public function test_product_content_is_html_escaped(): void
    {
        Product::factory()->create(['title' => '<script>alert(1)</script>']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_admin_product_image_must_be_an_http_url(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/admin/products', [
            'title' => 'Ring',
            'category' => 'jewelry',
            'price_usd' => 5,
            'stock' => 3,
            'image' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('image');
    }

    // ---------------------------------------------------------------------
    // API
    // ---------------------------------------------------------------------

    public function test_api_is_rate_limited_and_exposes_no_order_data(): void
    {
        $order = Order::factory()->create();

        $this->getJson('/api/products')->assertOk()->assertHeader('X-RateLimit-Limit', '60');
        $this->getJson("/api/orders/{$order->order_number}/status")->assertNotFound();
        $this->getJson('/api/user')->assertNotFound();
    }

    public function test_api_rejects_unknown_categories(): void
    {
        $this->getJson('/api/products?category=x%27%20OR%201=1')->assertStatus(422);
    }

    // ---------------------------------------------------------------------
    // Headers, CORS, configuration
    // ---------------------------------------------------------------------

    public function test_security_headers_are_sent(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("object-src 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_cors_does_not_allow_any_origin(): void
    {
        $this->assertNotContains('*', config('cors.allowed_origins'));

        $this->withHeaders(['Origin' => 'https://evil.example'])
            ->getJson('/api/products')
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_env_example_is_production_safe_and_holds_no_secrets(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $env);
        $this->assertMatchesRegularExpression('/^APP_ENV=production$/m', $env);
        $this->assertMatchesRegularExpression('/^APP_KEY=$/m', $env);
        $this->assertMatchesRegularExpression('/^DB_PASSWORD=$/m', $env);
        $this->assertMatchesRegularExpression('/^DB_SSLMODE=require$/m', $env);
        $this->assertMatchesRegularExpression('/^SESSION_SECURE_COOKIE=true$/m', $env);
        $this->assertDoesNotMatchRegularExpression('/AIza|AlzaSy|base64:/', $env);
    }

    public function test_pgsql_ssl_mode_follows_the_environment(): void
    {
        $config = require base_path('config/database.php');
        $source = file_get_contents(base_path('config/database.php'));

        $this->assertArrayHasKey('pgsql', $config['connections']);
        $this->assertStringContainsString("'sslmode' => env('DB_SSLMODE'", $source);
    }

    // ---------------------------------------------------------------------
    // Launch requirements
    // ---------------------------------------------------------------------

    public function test_legal_pages_exist_and_are_linked_from_every_storefront_page(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy');
        $this->get('/terms')->assertOk()->assertSee('Terms and Conditions');

        $this->get('/')
            ->assertSee(route('privacy'), false)
            ->assertSee(route('terms'), false)
            ->assertSee('favicon.ico', false);
    }

    public function test_static_prototype_and_secrets_are_not_web_accessible(): void
    {
        foreach (['home.html', 'admin-users.html', 'assets/js/rbac.js', 'metadata.json'] as $path) {
            $this->assertFileDoesNotExist(public_path($path));
        }
    }
}
