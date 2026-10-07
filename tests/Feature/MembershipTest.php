<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\Inventory;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Plus -> Pro at $100 spent (6% off) -> Max at $500 (17% off); 18 days without an order starts again. */
class MembershipTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'boss@example.com', 'password' => 'secret123', 'role' => 'admin']);
        Inventory::setQuantity(Product::where('sku', 'genz-01')->firstOrFail(), 900, $this->admin); // $9.00 each
    }

    /** Places an order of genz-01 and (optionally) delivers it. Returns the order JSON. */
    private function buy(User $buyer, int $quantity, bool $deliver = true, ?string $promo = null): array
    {
        $order = $this->actingAs($buyer->fresh())->postJson('/api/orders', [
            'customerName' => 'Dara Kim', 'phone' => '012 345 678', 'address' => 'St 63, Phnom Penh',
            'paymentMethod' => 'cod', 'items' => [['id' => 'genz-01', 'quantity' => $quantity]], 'promoCode' => $promo,
        ])->assertCreated()->json('order');
        if ($deliver) {
            foreach (['verify', 'dispatch', 'deliver'] as $step) {
                $this->actingAs($this->admin)->patchJson("/api/admin/orders/{$order['id']}", ['action' => $step])->assertOk();
            }
        }

        return $order;
    }

    private function loyalty(User $buyer): array
    {
        return $this->actingAs($buyer->fresh())->getJson('/api/bootstrap')->json('user.loyalty');
    }

    public function test_spending_unlocks_pro_then_max_and_their_discounts(): void
    {
        $buyer = User::create(['name' => 'Dara', 'email' => 'dara@example.com', 'password' => 'secret123', 'role' => 'buyer']);
        $this->assertSame('Plus', $this->loyalty($buyer)['tier']);

        // $108 delivered -> Pro
        $first = $this->buy($buyer, 12);
        $this->assertSame('0.00', $first['memberDiscountUSD']);
        $l = $this->loyalty($buyer);
        $this->assertEquals(['Pro', 6, '108.00', 'Max', '392.00'], [$l['tier'], $l['discountPercent'], $l['spendUSD'], $l['nextTier'], $l['spendToNextUSD']]);
        $this->assertNotNull($l['expiresAt']);

        // Pro: 6% off the items. 20 x $9 = $180 -> $10.80 off -> $169.20 (free delivery)
        $second = $this->buy($buyer, 20);
        $this->assertSame(['180.00', '10.80', '169.20'], [$second['subtotalUSD'], $second['memberDiscountUSD'], $second['totalUSD']]);

        // Undelivered orders don't count towards the tier yet
        $this->buy($buyer, 30, deliver: false);
        $this->assertSame('Pro', $this->loyalty($buyer)['tier']);

        // $108 + $169.20 + 30 x $9 less 6% ($253.80) = $531 -> Max: 17% off
        $this->buy($buyer, 30);
        $l = $this->loyalty($buyer);
        $this->assertEquals(['Max', 17, '531.00', null], [$l['tier'], $l['discountPercent'], $l['spendUSD'], $l['nextTier']]);
    }

    public function test_max_members_get_17_percent_and_a_promo_code_comes_off_what_is_left(): void
    {
        $buyer = User::create(['name' => 'Vanna', 'email' => 'vanna@example.com', 'password' => 'secret123', 'role' => 'buyer']);
        $this->buy($buyer, 60); // $540 delivered -> Max
        $this->assertEquals(['Max', 17], [$this->loyalty($buyer)['tier'], $this->loyalty($buyer)['discountPercent']]);

        PromoCode::create(['code' => 'TEN', 'discount_type' => 'percent', 'discount_value' => 10, 'is_active' => true]);
        // 10 x $9 = $90; 17% = $15.30 -> $74.70; 10% of that = $7.47 -> $67.23
        $this->actingAs($buyer->fresh())->postJson('/api/promo-codes/check', ['code' => 'TEN', 'items' => [['id' => 'genz-01', 'quantity' => 10]]])
            ->assertOk()->assertJsonPath('memberDiscountUSD', '15.30')->assertJsonPath('discountUSD', '7.47');
        $order = $this->buy($buyer, 10, deliver: false, promo: 'TEN');
        $this->assertSame(['15.30', '7.47', '67.23'], [$order['memberDiscountUSD'], $order['discountUSD'], $order['totalUSD']]);
    }

    public function test_membership_lapses_after_18_days_without_an_order_and_counting_starts_again(): void
    {
        $buyer = User::create(['name' => 'Sok', 'email' => 'sok@example.com', 'password' => 'secret123', 'role' => 'buyer']);
        $this->buy($buyer, 12); // $108 -> Pro
        $this->assertSame('Pro', $this->loyalty($buyer)['tier']);

        $this->travel(17)->days();
        $this->assertSame('Pro', $this->loyalty($buyer)['tier']);

        $this->travel(2)->days(); // 19 days since the last order
        $l = $this->loyalty($buyer);
        $this->assertSame(['Plus', '0.00', null], [$l['tier'], $l['spendUSD'], $l['expiresAt']]);

        // No member discount now, and the old $108 no longer counts: $54 more is not enough for Pro
        $order = $this->buy($buyer, 6);
        $this->assertSame('0.00', $order['memberDiscountUSD']);
        $this->assertSame(['Plus', '54.00'], [$this->loyalty($buyer)['tier'], $this->loyalty($buyer)['spendUSD']]);
    }
}
