<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Two shop rules for cleaning up chats, as site_settings rows (no new table):
 * chat.auto_delete_days: a chat with no new message for this many days is deleted automatically;
 * chat.purge_days: a deleted message is kept this many days, then removed for good.
 */
return new class extends Migration
{
    private const ROWS = ['chat.auto_delete_days' => '7', 'chat.purge_days' => '15'];

    public function up(): void
    {
        foreach (self::ROWS as $key => $value) {
            if (! DB::table('site_settings')->where('setting_key', $key)->exists()) {
                DB::table('site_settings')->insert(['setting_key' => $key, 'setting_value' => $value, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('setting_key', array_keys(self::ROWS))->delete();
    }
};
