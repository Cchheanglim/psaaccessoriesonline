<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Social accounts are saved as the account's link (many accounts share a name, a link points at
 * exactly one). Names saved before become links: "@psaonline_kh" -> https://www.instagram.com/psaonline_kh.
 * The website shows the name worked out from the link.
 */
return new class extends Migration
{
    private const BASE = [
        'social.facebook' => 'https://www.facebook.com/',
        'social.instagram' => 'https://www.instagram.com/',
        'social.tiktok' => 'https://www.tiktok.com/@',
        'social.x' => 'https://x.com/',
        'social.youtube' => 'https://www.youtube.com/@',
        'social.telegram' => 'https://t.me/',
    ];

    public function up(): void
    {
        foreach (self::BASE as $key => $base) {
            $value = trim((string) DB::table('site_settings')->where('setting_key', $key)->value('setting_value'));
            if ($value === '' || str_starts_with($value, 'https://')) {
                continue;
            }
            DB::table('site_settings')->where('setting_key', $key)->update([
                'setting_value' => $base.rawurlencode(ltrim($value, '@')),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('One-way: restore the database backup to go back.');
    }
};
