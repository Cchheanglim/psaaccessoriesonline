<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database redesign, step 2 of 5 ("Customers & Loyalty").
 *
 * users keeps the login; customers is a 1:1 profile for shoppers with their loyalty points,
 * tier and total spent. Saved addresses move to customer_addresses, every account gets a
 * user_settings row (theme, language, currency, notification switches), and past delivered
 * orders are turned into loyalty points so existing customers start with their history.
 */
return new class extends Migration
{
    private const TIERS = [
        ['name' => 'Bronze', 'min_points' => 0, 'discount_percent' => 0, 'earn_multiplier' => 1.00],
        ['name' => 'Silver', 'min_points' => 500, 'discount_percent' => 5, 'earn_multiplier' => 1.25],
        ['name' => 'Gold', 'min_points' => 1500, 'discount_percent' => 10, 'earn_multiplier' => 1.50],
    ];

    public function up(): void
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->unsignedInteger('min_points')->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('earn_multiplier', 3, 2)->default(1);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // 1:1 with users
            $table->foreignId('loyalty_tier_id')->constrained()->restrictOnDelete();
            $table->integer('loyalty_points')->default(0); // quick copy of SUM(loyalty_transactions.points)
            $table->decimal('total_spent_usd', 10, 2)->default(0);
            $table->timestamps();
            $table->index('loyalty_tier_id');
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->default('Home');
            $table->string('recipient_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address_line');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index('customer_id');
        });
        // At most one default address per customer
        DB::statement('CREATE UNIQUE INDEX customer_addresses_one_default ON customer_addresses (customer_id) WHERE '.$this->isTrue('is_default'));

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20); // earn, redeem, adjust, expire
            $table->integer('points');  // + earned, - used
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('customer_id');
            $table->index('order_id');
            $table->index('handled_by');
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete(); // 1:1 with users
            $table->string('theme', 10)->default('system');   // light, dark, system
            $table->string('language', 5)->default('en');     // en, km
            $table->string('currency', 3)->default('USD');    // USD, KHR
            $table->boolean('notify_orders')->default(true);
            $table->boolean('notify_promotions')->default(true);
            $table->boolean('notify_price_drops')->default(true);
            $table->timestamp('updated_at')->nullable();
        });

        $now = now();
        foreach (self::TIERS as $tier) {
            DB::table('loyalty_tiers')->insert($tier + ['created_at' => $now, 'updated_at' => $now]);
        }
        $tiers = DB::table('loyalty_tiers')->orderBy('min_points')->get();
        $bronzeId = $tiers->first()->id;
        $buyerRoleId = DB::table('roles')->where('name', 'buyer')->value('id');

        // A customer profile for every buyer, and for anyone else who has already ordered or reviewed.
        $customerUserIds = DB::table('users')->where('role_id', $buyerRoleId)->pluck('id')
            ->merge(DB::table('orders')->whereNotNull('user_id')->pluck('user_id'))
            ->merge(DB::table('product_reviews')->pluck('user_id'))
            ->unique()->values();

        foreach (DB::table('users')->whereIn('id', $customerUserIds)->orderBy('id')->get() as $user) {
            $customerId = DB::table('customers')->insertGetId([
                'user_id' => $user->id, 'loyalty_tier_id' => $bronzeId, 'loyalty_points' => 0, 'total_spent_usd' => 0,
                'created_at' => $user->created_at ?? $now, 'updated_at' => $now,
            ]);

            if (trim((string) $user->address) !== '') {
                DB::table('customer_addresses')->insert([
                    'customer_id' => $customerId, 'label' => 'Home', 'recipient_name' => $user->name, 'phone' => $user->phone,
                    'address_line' => $user->address, 'is_default' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            // Loyalty history: 1 point per whole dollar of each delivered order (Bronze earn rate).
            $points = 0;
            $spent = 0.0;
            $delivered = DB::table('orders')->where('user_id', $user->id)->where('order_status', 'delivered')->orderBy('id')->get();
            foreach ($delivered as $order) {
                $earned = (int) floor((float) $order->total_usd);
                $spent += (float) $order->total_usd;
                if ($earned > 0) {
                    DB::table('loyalty_transactions')->insert([
                        'customer_id' => $customerId, 'order_id' => $order->id, 'type' => 'earn', 'points' => $earned,
                        'note' => "Points for delivered order {$order->order_number}", 'created_at' => $order->updated_at ?? $now,
                    ]);
                    $points += $earned;
                }
            }
            $tierId = $tiers->where('min_points', '<=', $points)->sortByDesc('min_points')->first()->id;
            DB::table('customers')->where('id', $customerId)->update([
                'loyalty_points' => $points, 'total_spent_usd' => round($spent, 2), 'loyalty_tier_id' => $tierId,
            ]);
        }

        foreach (DB::table('users')->pluck('id') as $userId) {
            DB::table('user_settings')->insert(['user_id' => $userId, 'updated_at' => $now]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }

    private function isTrue(string $column): string
    {
        return DB::getDriverName() === 'pgsql' ? $column : "{$column} = 1";
    }

    public function down(): void
    {
        throw new RuntimeException('The 2026-10 database redesign is one-way. Restore the database from a backup to go back.');
    }
};
