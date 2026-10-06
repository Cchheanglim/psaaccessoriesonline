<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Loyalty tiers are called Plus, Pro and Max (were Bronze, Silver, Gold). Same points and perks.
 * - user_settings is removed: theme, language and notification choices stay in the visitor's browser,
 *   so the database only keeps what the shop needs.
 */
return new class extends Migration
{
    private const NAMES = ['Plus', 'Pro', 'Max'];

    public function up(): void
    {
        // Lowest tier first: Bronze -> Plus, Silver -> Pro, Gold -> Max.
        foreach (DB::table('loyalty_tiers')->orderBy('min_points')->pluck('id')->values() as $i => $id) {
            if (isset(self::NAMES[$i])) {
                DB::table('loyalty_tiers')->where('id', $id)->update(['name' => self::NAMES[$i], 'updated_at' => now()]);
            }
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE user_settings DROP CONSTRAINT IF EXISTS user_settings_theme_check');
            DB::statement('ALTER TABLE user_settings DROP CONSTRAINT IF EXISTS user_settings_language_check');
        }
        Schema::dropIfExists('user_settings');
    }

    public function down(): void
    {
        throw new RuntimeException('One-way: restore the database backup to go back.');
    }
};
