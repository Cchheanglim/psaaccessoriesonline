<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Everything on the website staff can change without coding: one row = one piece of text
 * (or one number) shown on the site. setting_key is the primary key, e.g. "header.announcement_1".
 * FIELDS lists every key the website uses, its group on the staff Website page and its original text.
 */
class SiteSetting extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'setting_key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['setting_key', 'setting_value'];

    /** Social platforms, in the order they are shown. The value is the account name; the link is built from it. */
    public const SOCIAL_PLATFORMS = ['facebook', 'instagram', 'tiktok', 'x', 'youtube', 'telegram'];

    /**
     * key => [group, label, original value, max length | [min, max] for numbers, type, hint]
     * Types: text, long (several lines), image, url (a social account's link), money, percent, days.
     * Groups: header, home, footer, social (staff with "Edit products & shop"), rules (admins only).
     */
    public const FIELDS = [
        // Header (and the brand in the footer)
        'site.name' => ['header', 'Shop name', 'PsaOnline', 40, 'text', 'Shown next to the logo in the header and footer.'],
        'site.tagline' => ['header', 'Tagline', 'Gifts & cute finds', 60, 'text', 'The small line under the shop name.'],
        'site.logo' => ['header', 'Logo', 'assets/images/psa-accessories-online-logo.svg', 600000, 'image', 'A square or wide picture, PNG, JPG, WebP or SVG.'],
        'header.announcement_1' => ['header', 'Top bar message 1', 'Free delivery in Phnom Penh on orders $15+', 90, 'text', 'Leave a message empty to hide it.'],
        'header.announcement_2' => ['header', 'Top bar message 2', 'Pay by KHQR or cash on delivery', 90, 'text', ''],
        'header.announcement_3' => ['header', 'Top bar message 3', 'All prices in US dollars', 90, 'text', ''],
        'header.search_placeholder' => ['header', 'Search box hint', 'Search charms, clips, socks, tees…', 60, 'text', 'Grey text inside the search box.'],

        // Home page
        'home.headline' => ['home', 'Headline', 'Cute gifts & *little treats,* delivered in Phnom Penh.', 90, 'text', 'Put *stars* around words to give them the orange highlight.'],
        'home.subtitle' => ['home', 'Line under the headline', 'Plush bag charms, flower claw clips, comfy socks, graphic tees and watches. Real prices in US dollars, paid your way.', 220, 'long', ''],
        'home.button_primary' => ['home', 'Main button', 'Shop all {count} finds', 40, 'text', '{count} becomes the number of products.'],
        'home.button_secondary' => ['home', 'Second button', 'Gifts under $10', 40, 'text', ''],
        'home.chip_1' => ['home', 'Info chip 1', '$1.50 delivery, free from $15', 60, 'text', 'Leave empty to hide.'],
        'home.chip_2' => ['home', 'Info chip 2', 'KHQR or cash on delivery', 60, 'text', 'Leave empty to hide.'],
        'home.gifts_title' => ['home', 'Gift ideas title', 'Find the right little gift', 60, 'text', ''],
        'home.gifts_text' => ['home', 'Gift ideas text', "Start with who it's for, or how much you want to spend.", 160, 'text', ''],
        'home.picks_title' => ['home', 'New products title', 'Fresh picks', 60, 'text', ''],
        'home.steps_title' => ['home', 'How it works title', 'Pick it. Pay it. *Get it.*', 60, 'text', 'Words between *stars* turn orange.'],
        'home.step_1_title' => ['home', 'Step 1 title', 'Pick something cute', 50, 'text', ''],
        'home.step_1_text' => ['home', 'Step 1 text', 'Browse freely and add to your bag. You sign in when you check out.', 160, 'long', ''],
        'home.step_2_title' => ['home', 'Step 2 title', 'Pay your way', 50, 'text', ''],
        'home.step_2_text' => ['home', 'Step 2 text', 'Scan our KHQR with ABA, ACLEDA, Wing or any Cambodian banking app, or pay cash when it arrives.', 160, 'long', ''],
        'home.step_3_title' => ['home', 'Step 3 title', 'Delivered in Phnom Penh', 50, 'text', ''],
        'home.step_3_text' => ['home', 'Step 3 text', '$1.50 delivery, free on orders of $15 or more. Track it from your account.', 160, 'long', ''],
        'home.help_title' => ['home', 'Help box title', 'Not sure what to pick?', 60, 'text', ''],
        'home.help_text' => ['home', 'Help box text', "Message us on Telegram with who it's for and your budget. We'll help you choose.", 160, 'long', ''],
        'home.help_button' => ['home', 'Help box button', 'Chat on Telegram', 40, 'text', 'Opens the Telegram account from Social media.'],

        // Footer
        'footer.about_text' => ['footer', 'About the shop', 'Cute gifts and everyday finds, delivered in Phnom Penh. Prices in US dollars, paid by KHQR or cash on delivery.', 220, 'long', ''],
        'footer.payment_text' => ['footer', 'Payments accepted', 'Bakong KHQR (ABA, ACLEDA, Canadia, Wing and other Cambodian banking apps) and cash on delivery.', 200, 'long', ''],
        'footer.phone' => ['footer', 'Phone', '', 30, 'text', 'Leave empty to hide.'],
        'footer.email' => ['footer', 'Email', '', 100, 'text', 'Leave empty to hide.'],
        'footer.address' => ['footer', 'Address', 'Phnom Penh, Cambodia', 160, 'text', 'Leave empty to hide.'],
        'footer.hours' => ['footer', 'Opening hours', '', 80, 'text', 'Leave empty to hide.'],
        'footer.copyright' => ['footer', 'Copyright line', '© 2026 PsaOnline', 80, 'text', ''],

        // Social accounts (empty = not shown)
        'social.facebook' => ['social', 'Facebook', 'https://www.facebook.com/PsaOnline', 255, 'url', 'Paste the link from your Facebook page.'],
        'social.instagram' => ['social', 'Instagram', 'https://www.instagram.com/psaonline_kh', 255, 'url', ''],
        'social.tiktok' => ['social', 'TikTok', 'https://www.tiktok.com/@psaonline', 255, 'url', ''],
        'social.x' => ['social', 'X', 'https://x.com/psaonline', 255, 'url', ''],
        'social.youtube' => ['social', 'YouTube', '', 255, 'url', ''],
        'social.telegram' => ['social', 'Telegram', 'https://t.me/psaonline_support', 255, 'url', 'Also used by every "Message us on Telegram" link.'],

        // Shop rules (admins only)
        'shop.delivery_fee_usd' => ['rules', 'Delivery fee', '1.50', [0, 100], 'money', 'Charged when the items are under the free delivery amount.'],
        'shop.free_delivery_from_usd' => ['rules', 'Free delivery from', '15', [0, 10000], 'money', 'Items worth this much or more ship free.'],
        'membership.pro_min_spend_usd' => ['rules', 'Pro: spend needed', '100', [1, 100000], 'money', 'Delivered orders a customer needs to become Pro.'],
        'membership.pro_discount_percent' => ['rules', 'Pro: discount', '6', [0, 90], 'percent', ''],
        'membership.max_min_spend_usd' => ['rules', 'Max: spend needed', '500', [1, 100000], 'money', 'Must be more than Pro.'],
        'membership.max_discount_percent' => ['rules', 'Max: discount', '17', [0, 90], 'percent', ''],
        'membership.lapse_days' => ['rules', 'Membership ends after', '18', [1, 365], 'days', 'Days without an order before a customer goes back to Plus.'],
    ];

    /** The web addresses each platform's links may use (so a pasted link really is that platform). */
    public const SOCIAL_HOSTS = [
        'facebook' => ['facebook.com', 'fb.com', 'm.facebook.com'],
        'instagram' => ['instagram.com'],
        'tiktok' => ['tiktok.com'],
        'x' => ['x.com', 'twitter.com'],
        'youtube' => ['youtube.com', 'youtu.be', 'm.youtube.com'],
        'telegram' => ['t.me', 'telegram.me'],
    ];

    /** Is this an https link to one of the platform's sites, with an account in it? */
    public static function isSocialUrl(string $platform, string $url): bool
    {
        $parts = parse_url($url);
        if (! $parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || trim($parts['path'] ?? '', '/') === '') {
            return false;
        }
        $host = preg_replace('/^www\./', '', strtolower($parts['host']));

        return in_array($host, self::SOCIAL_HOSTS[$platform] ?? [], true);
    }

    /** The account name shown for a link: https://www.instagram.com/psaonline_kh -> @psaonline_kh */
    public static function socialName(string $platform, string $url): string
    {
        $segments = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));
        $name = $segments[0] ?? '';
        if ($name === 'channel') {
            return 'YouTube channel'; // a channel id is not a name
        }
        if (in_array($name, ['c', 'user', 'pages', 'people'], true)) {
            $name = $segments[1] ?? $name;
        }
        $name = rawurldecode($name);
        if ($name === '' || $name === 'profile.php') {
            return ucfirst($platform);
        }

        return $platform === 'facebook' || str_starts_with($name, '@') ? $name : '@'.$name;
    }

    /** Every setting's value (the original text when a row is missing). Cached until a setting is saved. */
    public static function map(): array
    {
        return Cache::rememberForever('site_settings', function () {
            $saved = static::query()->pluck('setting_value', 'setting_key')->all();

            return collect(self::FIELDS)->map(fn ($f, $key) => array_key_exists($key, $saved) ? (string) $saved[$key] : $f[2])->all();
        });
    }

    public static function get(string $key): string
    {
        return self::map()[$key] ?? (self::FIELDS[$key][2] ?? '');
    }

    public static function number(string $key): float
    {
        return (float) self::get($key);
    }

    /** Saves several settings at once; null puts the original text back. */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['setting_key' => $key], ['setting_value' => $value ?? self::FIELDS[$key][2]]);
        }
        Cache::forget('site_settings');
    }

    public static function forgetCache(): void
    {
        Cache::forget('site_settings');
    }
}
