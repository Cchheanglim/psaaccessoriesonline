<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Staff portal > Website: header, home page, footer and social accounts, and the shop rules (admins). */
class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
        SiteSetting::forgetCache();
    }

    private function user(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create(['name' => "Person {$n}", 'email' => "site{$n}@example.com", 'password' => 'secret123', 'role' => $role]);
    }

    public function test_every_setting_is_a_row_and_the_website_gets_them_all(): void
    {
        // One row per piece of text, three columns (setting_key, setting_value, updated_at)
        $this->assertSame(count(SiteSetting::FIELDS), DB::table('site_settings')->count());
        $this->assertSame('Free delivery in Phnom Penh on orders $15+', DB::table('site_settings')->where('setting_key', 'header.announcement_1')->value('setting_value'));
        $this->assertSame('@psaonline_support', DB::table('site_settings')->where('setting_key', 'social.telegram')->value('setting_value'));

        $settings = $this->getJson('/api/bootstrap')->assertOk()->json('settings');
        $this->assertSame('PsaOnline', $settings['site.name']);
        $this->assertSame('Pick it. Pay it. *Get it.*', $settings['home.steps_title']);
        $this->assertSame('15', $settings['shop.free_delivery_from_usd']);
    }

    public function test_staff_edit_the_header_home_and_footer_and_empty_puts_the_original_back(): void
    {
        $staff = $this->user('staff');
        $this->actingAs($this->user('buyer'))->putJson('/api/admin/site-settings', ['settings' => ['site.tagline' => 'Hi']])->assertStatus(403);

        $this->actingAs($staff)->putJson('/api/admin/site-settings', ['settings' => [
            'site.tagline' => 'Water Festival gifts',
            'header.announcement_2' => '',              // messages can be hidden
            'home.headline' => 'Happy *Water Festival!*',
            'footer.phone' => '012 345 678',
            'social.youtube' => '@psaonline',
            'social.x' => '',                           // hide X
        ]])->assertOk();

        $settings = $this->getJson('/api/bootstrap')->json('settings');
        $this->assertSame(['Water Festival gifts', '', 'Happy *Water Festival!*', '012 345 678', '@psaonline', ''],
            [$settings['site.tagline'], $settings['header.announcement_2'], $settings['home.headline'], $settings['footer.phone'], $settings['social.youtube'], $settings['social.x']]);

        // Empty text that can't be hidden goes back to the original
        $this->actingAs($staff)->putJson('/api/admin/site-settings', ['settings' => ['home.headline' => '', 'site.tagline' => null]])->assertOk();
        $this->assertSame('Cute gifts & *little treats,* delivered in Phnom Penh.', SiteSetting::get('home.headline'));
        $this->assertSame('Gifts & cute finds', SiteSetting::get('site.tagline'));

        // Limits
        $this->actingAs($staff)->putJson('/api/admin/site-settings', ['settings' => ['home.headline' => str_repeat('a', 91)]])->assertStatus(422);
        $this->actingAs($staff)->putJson('/api/admin/site-settings', ['settings' => ['social.instagram' => 'https://instagram.com/x y']])->assertStatus(422);
        $this->actingAs($staff)->putJson('/api/admin/site-settings', ['settings' => ['footer.email' => 'not-an-email']])->assertStatus(422);
        $this->actingAs($staff)->putJson('/api/admin/site-settings', ['settings' => ['nope.key' => 'x']])->assertStatus(422);
        $this->actingAs($staff)->putJson('/api/admin/site-settings', ['settings' => ['site.logo' => 'javascript:alert(1)']])->assertStatus(422);
    }

    public function test_only_admins_change_the_shop_rules_and_checkout_follows_them(): void
    {
        $this->actingAs($this->user('staff'))->putJson('/api/admin/site-settings', ['settings' => ['shop.delivery_fee_usd' => '2']])->assertStatus(403);

        $admin = $this->user('admin');
        $this->actingAs($admin)->putJson('/api/admin/site-settings', ['settings' => ['membership.max_min_spend_usd' => '50']])->assertStatus(422); // Max must be above Pro
        $this->actingAs($admin)->putJson('/api/admin/site-settings', ['settings' => ['shop.delivery_fee_usd' => 'free']])->assertStatus(422);
        $this->actingAs($admin)->putJson('/api/admin/site-settings', ['settings' => [
            'shop.delivery_fee_usd' => '2', 'shop.free_delivery_from_usd' => '30', 'membership.pro_min_spend_usd' => '20', 'membership.pro_discount_percent' => '10',
        ]])->assertOk();

        // genz-01 is $9: 2 x $9 = $18, now under the $30 free-delivery amount -> $2 delivery
        $buyer = $this->user('buyer');
        $order = $this->actingAs($buyer)->postJson('/api/orders', [
            'customerName' => 'Dara Kim', 'phone' => '012 345 678', 'address' => 'St 63, Phnom Penh',
            'paymentMethod' => 'cod', 'items' => [['id' => 'genz-01', 'quantity' => 2]],
        ])->assertCreated()->json('order');
        $this->assertSame(['18.00', '2.00', '20.00'], [$order['subtotalUSD'], $order['deliveryUSD'], $order['totalUSD']]);

        // Pro now needs $20 and gives 10%
        foreach (['verify', 'dispatch', 'deliver'] as $step) {
            $this->actingAs($admin)->patchJson("/api/admin/orders/{$order['id']}", ['action' => $step])->assertOk();
        }
        $loyalty = $this->actingAs($buyer->fresh())->getJson('/api/bootstrap')->json('user.loyalty');
        $this->assertSame('Pro', $loyalty['tier']);
        $this->assertEquals(10, $loyalty['discountPercent']);
    }
}
