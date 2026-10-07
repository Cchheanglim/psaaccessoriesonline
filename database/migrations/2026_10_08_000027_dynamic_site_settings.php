<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The website is edited from the staff portal (Website page): one site_settings row per piece of text
 * on the header, home page and footer, one row per social account, and the shop rules.
 *  - site_settings becomes setting_key (PK), setting_value, updated_at.
 *  - social.links (a JSON list in one cell) becomes one row per platform: social.facebook, ... (1NF).
 *  - loyalty_tiers is merged in: Pro / Max amounts and discounts and the 18-day rule are rows
 *    (membership.*), so admins can change them. Plus is everyone below Pro.
 *  - Every setting gets a row with the text the site showed until now.
 */
return new class extends Migration
{
    /** The text the website showed until now, so nothing changes for visitors on the day this runs. */
    private const ORIGINAL = [
        'site.name' => 'PsaOnline',
        'site.tagline' => 'Gifts & cute finds',
        'site.logo' => 'assets/images/psa-accessories-online-logo.svg',
        'header.announcement_1' => 'Free delivery in Phnom Penh on orders $15+',
        'header.announcement_2' => 'Pay by KHQR or cash on delivery',
        'header.announcement_3' => 'All prices in US dollars',
        'header.search_placeholder' => 'Search charms, clips, socks, tees…',
        'home.headline' => 'Cute gifts & *little treats,* delivered in Phnom Penh.',
        'home.subtitle' => 'Plush bag charms, flower claw clips, comfy socks, graphic tees and watches. Real prices in US dollars, paid your way.',
        'home.button_primary' => 'Shop all {count} finds',
        'home.button_secondary' => 'Gifts under $10',
        'home.chip_1' => '$1.50 delivery, free from $15',
        'home.chip_2' => 'KHQR or cash on delivery',
        'home.gifts_title' => 'Find the right little gift',
        'home.gifts_text' => "Start with who it's for, or how much you want to spend.",
        'home.picks_title' => 'Fresh picks',
        'home.steps_title' => 'Pick it. Pay it. *Get it.*',
        'home.step_1_title' => 'Pick something cute',
        'home.step_1_text' => 'Browse freely and add to your bag. You sign in when you check out.',
        'home.step_2_title' => 'Pay your way',
        'home.step_2_text' => 'Scan our KHQR with ABA, ACLEDA, Wing or any Cambodian banking app, or pay cash when it arrives.',
        'home.step_3_title' => 'Delivered in Phnom Penh',
        'home.step_3_text' => '$1.50 delivery, free on orders of $15 or more. Track it from your account.',
        'home.help_title' => 'Not sure what to pick?',
        'home.help_text' => "Message us on Telegram with who it's for and your budget. We'll help you choose.",
        'home.help_button' => 'Chat on Telegram',
        'footer.about_text' => 'Cute gifts and everyday finds, delivered in Phnom Penh. Prices in US dollars, paid by KHQR or cash on delivery.',
        'footer.payment_text' => 'Bakong KHQR (ABA, ACLEDA, Canadia, Wing and other Cambodian banking apps) and cash on delivery.',
        'footer.phone' => '',
        'footer.email' => '',
        'footer.address' => 'Phnom Penh, Cambodia',
        'footer.hours' => '',
        'footer.copyright' => '© 2026 PsaOnline',
        'social.facebook' => 'PsaOnline',
        'social.instagram' => '@psaonline_kh',
        'social.tiktok' => '@psaonline',
        'social.x' => '@psaonline',
        'social.youtube' => '',
        'social.telegram' => '@psaonline_support',
        'shop.delivery_fee_usd' => '1.50',
        'shop.free_delivery_from_usd' => '15',
        'membership.pro_min_spend_usd' => '100',
        'membership.pro_discount_percent' => '6',
        'membership.max_min_spend_usd' => '500',
        'membership.max_discount_percent' => '17',
        'membership.lapse_days' => '18',
    ];

    public function up(): void
    {
        $now = now();
        $saved = DB::table('site_settings')->pluck('value', 'key')->all();

        // A saved social list becomes one row per platform; platforms left out of it are hidden ('').
        $social = [];
        if (! empty($saved['social.links'])) {
            $list = json_decode($saved['social.links'], true) ?: [];
            foreach (['facebook', 'instagram', 'tiktok', 'x', 'youtube', 'telegram'] as $platform) {
                $social["social.{$platform}"] = '';
            }
            foreach ($list as $link) {
                if (! empty($link['platform']) && isset($social["social.{$link['platform']}"])) {
                    $social["social.{$link['platform']}"] = (string) ($link['handle'] ?? '');
                }
            }
        }

        // The membership amounts from loyalty_tiers, by tier name.
        $tiers = Schema::hasTable('loyalty_tiers') ? DB::table('loyalty_tiers')->get()->keyBy('name') : collect();
        $membership = [];
        foreach (['Pro' => 'pro', 'Max' => 'max'] as $name => $key) {
            if ($tier = $tiers->get($name)) {
                $membership["membership.{$key}_min_spend_usd"] = rtrim(rtrim(number_format((float) $tier->min_spend_usd, 2, '.', ''), '0'), '.');
                $membership["membership.{$key}_discount_percent"] = rtrim(rtrim(number_format((float) $tier->discount_percent, 2, '.', ''), '0'), '.');
            }
        }

        $values = array_merge(self::ORIGINAL, array_intersect_key($saved, self::ORIGINAL), $social, $membership);

        Schema::drop('site_settings');
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('setting_key', 80)->primary();
            $table->text('setting_value');
            $table->timestamp('updated_at')->nullable();
        });
        DB::table('site_settings')->insert(collect($values)->map(fn ($value, $key) => [
            'setting_key' => $key, 'setting_value' => $value, 'updated_at' => $now,
        ])->values()->all());

        Schema::dropIfExists('loyalty_tiers');
    }

    public function down(): void
    {
        throw new RuntimeException('One-way: restore the database backup to go back.');
    }
};
