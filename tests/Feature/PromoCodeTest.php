<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PromoCode;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Staff portal > Promo codes, and typing a code at checkout. */
class PromoCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
        Role::flushCache();
    }

    private function user(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create(['name' => "Person {$n}", 'email' => "promo{$n}@example.com", 'password' => 'secret123', 'role' => $role]);
    }

    /** genz-01 is $9.00; two of them = $18 (free delivery from $15). */
    private function checkout(User $buyer, ?string $code, int $quantity = 2)
    {
        return $this->actingAs($buyer)->postJson('/api/orders', [
            'customerName' => 'Dara Kim', 'phone' => '012 345 678', 'address' => 'St 63, Phnom Penh',
            'paymentMethod' => 'cod', 'items' => [['id' => 'genz-01', 'quantity' => $quantity]], 'promoCode' => $code,
        ]);
    }

    public function test_only_people_with_the_promo_codes_permission_manage_codes(): void
    {
        $code = ['code' => 'summer 10', 'discountType' => 'percent', 'discountValue' => 10];
        $this->actingAs($this->user('buyer'))->postJson('/api/admin/promo-codes', $code)->assertStatus(403);
        $this->actingAs($this->user('staff'))->postJson('/api/admin/promo-codes', $code)->assertStatus(403);

        $admin = $this->user('admin');
        $this->actingAs($admin)->postJson('/api/admin/promo-codes', $code)->assertCreated()
            ->assertJsonPath('promoCode.code', 'SUMMER10')->assertJsonPath('promoCode.label', '10% off')->assertJsonPath('promoCode.state', 'active');
        $this->actingAs($admin)->postJson('/api/admin/promo-codes', $code)->assertStatus(422); // taken
        $this->actingAs($admin)->postJson('/api/admin/promo-codes', ['code' => 'HALF', 'discountType' => 'percent', 'discountValue' => 150])->assertStatus(422);
        $this->actingAs($admin)->postJson('/api/admin/promo-codes', ['code' => 'DATES', 'discountType' => 'fixed', 'discountValue' => 2, 'startsAt' => '2026-12-10', 'endsAt' => '2026-12-01'])->assertStatus(422);

        // ticking "Promo codes" for Staff lets staff in
        $this->actingAs($admin)->patchJson('/api/admin/roles/'.Role::idFor('staff'), ['permissions' => ['manage_orders', 'manage_promotions']])->assertOk();
        $this->actingAs($this->user('staff'))->getJson('/api/admin/promo-codes')->assertOk()->assertJsonCount(1, 'promoCodes');
    }

    public function test_a_customer_gets_the_discount_at_checkout_and_the_server_checks_the_rules(): void
    {
        PromoCode::create(['code' => 'SAVE5', 'discount_type' => 'fixed', 'discount_value' => 5, 'min_order_usd' => 15, 'max_uses_per_customer' => 1, 'is_active' => true]);
        $buyer = $this->user('buyer');

        // the preview at checkout
        $this->actingAs($buyer)->postJson('/api/promo-codes/check', ['code' => 'save5', 'items' => [['id' => 'genz-01', 'quantity' => 2]]])
            ->assertOk()->assertJsonPath('code', 'SAVE5')->assertJsonPath('discountUSD', '5.00');
        $this->actingAs($buyer)->postJson('/api/promo-codes/check', ['code' => 'SAVE5', 'items' => [['id' => 'genz-01', 'quantity' => 1]]])
            ->assertStatus(422)->assertJsonValidationErrors('code');
        $this->actingAs($buyer)->postJson('/api/promo-codes/check', ['code' => 'NOPE', 'items' => [['id' => 'genz-01', 'quantity' => 1]]])->assertStatus(422);

        // placing the order: $18 items - $5 = $13, delivery stays free (worked out before the discount)
        $this->checkout($buyer, 'SAVE5', 1)->assertStatus(422)->assertJsonValidationErrors('promoCode');
        $res = $this->checkout($buyer, ' save5 ')->assertCreated()
            ->assertJsonPath('order.subtotalUSD', '18.00')->assertJsonPath('order.discountUSD', '5.00')
            ->assertJsonPath('order.totalUSD', '13.00')->assertJsonPath('order.promoCode', 'SAVE5');
        $this->assertSame('13.00', number_format((float) Order::first()->latestPayment->amount_usd, 2));

        // once per customer; a cancelled order gives the use back
        $this->checkout($buyer, 'SAVE5')->assertStatus(422);
        $this->actingAs($this->user('admin'))->patchJson('/api/admin/orders/'.$res->json('order.id'), ['action' => 'cancel'])->assertOk();
        $this->checkout($buyer->fresh(), 'SAVE5')->assertCreated();
    }

    public function test_limits_dates_and_turned_off_codes_are_refused(): void
    {
        PromoCode::create(['code' => 'ONCE', 'discount_type' => 'percent', 'discount_value' => 50, 'max_uses' => 1, 'is_active' => true]);
        PromoCode::create(['code' => 'LATER', 'discount_type' => 'percent', 'discount_value' => 10, 'starts_at' => now()->addWeek(), 'is_active' => true]);
        PromoCode::create(['code' => 'OLD', 'discount_type' => 'percent', 'discount_value' => 10, 'ends_at' => now()->subDay(), 'is_active' => true]);
        PromoCode::create(['code' => 'OFF', 'discount_type' => 'percent', 'discount_value' => 10, 'is_active' => false]);
        PromoCode::create(['code' => 'BIG', 'discount_type' => 'fixed', 'discount_value' => 500, 'is_active' => true, 'show_to_customers' => true]);

        $this->checkout($this->user('buyer'), 'ONCE')->assertCreated()->assertJsonPath('order.discountUSD', '9.00');
        $this->checkout($this->user('buyer'), 'ONCE')->assertStatus(422);
        foreach (['LATER', 'OLD', 'OFF'] as $code) {
            $this->checkout($this->user('buyer'), $code)->assertStatus(422);
        }
        // never more than the items cost
        $this->checkout($this->user('buyer'), 'BIG')->assertCreated()->assertJsonPath('order.discountUSD', '18.00')->assertJsonPath('order.totalUSD', '0.00');

        // only codes marked "show to customers" that work now appear under My coupons
        $this->getJson('/api/bootstrap')->assertOk()->assertJsonPath('coupons.0.code', 'BIG')->assertJsonCount(1, 'coupons');
    }

    public function test_a_used_code_keeps_its_discount_and_is_turned_off_instead_of_deleted(): void
    {
        $admin = $this->user('admin');
        $id = $this->actingAs($admin)->postJson('/api/admin/promo-codes', ['code' => 'FIVE', 'discountType' => 'fixed', 'discountValue' => 5])->json('promoCode.id');
        $this->checkout($this->user('buyer'), 'FIVE')->assertCreated();

        $this->actingAs($admin)->getJson('/api/admin/promo-codes')->assertJsonPath('promoCodes.0.uses', 1)->assertJsonPath('promoCodes.0.discountGivenUSD', '5.00');
        $this->actingAs($admin)->patchJson("/api/admin/promo-codes/{$id}", ['discountValue' => 8])->assertStatus(422);
        $this->actingAs($admin)->patchJson("/api/admin/promo-codes/{$id}", ['description' => 'Back to school', 'maxUses' => 50])->assertOk();
        $this->actingAs($admin)->deleteJson("/api/admin/promo-codes/{$id}")->assertOk()->assertJsonPath('turnedOff', true);
        $this->assertFalse(PromoCode::find($id)->is_active);

        $unused = $this->actingAs($admin)->postJson('/api/admin/promo-codes', ['code' => 'TEMP', 'discountType' => 'fixed', 'discountValue' => 1])->json('promoCode.id');
        $this->actingAs($admin)->deleteJson("/api/admin/promo-codes/{$unused}")->assertOk()->assertJsonPath('turnedOff', false);
        $this->assertNull(PromoCode::find($unused));
    }
}
