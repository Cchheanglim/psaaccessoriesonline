<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Runs the 2026-10 redesign migrations on a database that still has the OLD tables and data,
 * and checks every row arrives in the new tables with the same values (nothing is lost).
 */
class DataMigrationTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = tempnam(sys_get_temp_dir(), 'psa-migration-').'.sqlite';
        touch($this->file);
        config(['database.connections.legacy' => ['driver' => 'sqlite', 'database' => $this->file, 'prefix' => '', 'foreign_key_constraints' => true]]);

        // Rehearsal on PostgreSQL (like Supabase): MIGRATION_TEST_PGSQL=host:port/database:user:password
        // on a THROWAWAY database. Its public schema is wiped first, so never point it at a real one.
        if ($pg = env('MIGRATION_TEST_PGSQL')) {
            [$hostPort, $rest] = explode('/', $pg, 2);
            [$host, $port] = explode(':', $hostPort);
            [$database, $username, $password] = explode(':', $rest);
            config(['database.connections.legacy' => [
                'driver' => 'pgsql', 'host' => $host, 'port' => $port, 'database' => $database,
                'username' => $username, 'password' => $password, 'charset' => 'utf8', 'prefix' => '', 'schema' => 'public', 'sslmode' => 'disable',
            ]]);
            DB::connection('legacy')->statement('DROP SCHEMA public CASCADE');
            DB::connection('legacy')->statement('CREATE SCHEMA public');
        }

        $old = array_values(array_filter(glob(database_path('migrations/*.php')), fn ($f) => strcmp(basename($f), '2026_10_06') < 0)); // everything before the redesign
        Artisan::call('migrate', ['--database' => 'legacy', '--path' => $old, '--realpath' => true, '--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('legacy');
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_existing_data_moves_into_the_new_tables(): void
    {
        $db = DB::connection('legacy');
        $now = now();

        $db->table('users')->insert([
            ['id' => 1, 'name' => 'Store Admin', 'email' => 'admin@example.com', 'phone' => '011', 'password' => 'x', 'role' => 'admin', 'address' => 'Office', 'status' => 'Active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Dara Sok', 'email' => 'dara@example.com', 'phone' => '012', 'password' => 'x', 'role' => 'buyer', 'address' => 'St 240, Phnom Penh', 'status' => 'Active', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Sokha Lim', 'email' => 'sokha@example.com', 'phone' => '', 'password' => 'x', 'role' => 'staff', 'address' => null, 'status' => 'Active', 'created_at' => $now, 'updated_at' => $now],
        ]);
        $db->table('products')->insert([
            ['id' => 10, 'sku' => 'genz-01', 'title' => 'Ring', 'slug' => 'ring', 'category' => 'jewelry', 'category_label' => 'Jewelry', 'price_usd' => 9, 'price_khr' => 36900, 'stock' => 20, 'image' => '/a.jpg', 'gallery' => json_encode(['/a.jpg', '/b.jpg', '/c.jpg']), 'specifications' => json_encode(['Metal' => 'Silver']), 'description' => 'Shiny', 'material' => 'Silver', 'status' => 'active', 'rating' => 4, 'review_count' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'sku' => 'genz-02', 'title' => 'Jeans', 'slug' => 'jeans', 'category' => 'apparel', 'category_label' => 'Jeans', 'price_usd' => 20, 'price_khr' => 82000, 'stock' => 5, 'image' => '/j.jpg', 'gallery' => json_encode([]), 'specifications' => json_encode([]), 'description' => null, 'material' => null, 'status' => 'active', 'rating' => 0, 'review_count' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $db->table('payment_methods')->insert(['id' => 1, 'name' => 'Bakong', 'code' => 'bakong_khqr', 'type' => 'khqr', 'qr_data' => 'data:image/png;base64,AA', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $order = fn (int $id, string $status, string $payment, ?string $slip, ?int $handler, float $subtotal = 18, string $address = 'St 240') => [
            'id' => $id, 'order_number' => "PSA-{$id}", 'user_id' => 2, 'handled_by' => $handler, 'customer_name' => 'Dara Sok', 'customer_phone' => '012',
            'delivery_address' => $address, 'latitude' => 11.55, 'longitude' => 104.92, 'subtotal_usd' => $subtotal, 'subtotal_khr' => (int) ($subtotal * 4100),
            'delivery_fee_usd' => 1.5, 'delivery_fee_khr' => 6150, 'total_usd' => $subtotal + 1.5, 'total_khr' => (int) (($subtotal + 1.5) * 4100), 'payment_method' => 'bakong_khqr',
            'payment_status' => $payment, 'order_status' => $status, 'payment_slip_url' => $slip, 'paid_at' => $payment === 'verified' ? $now : null,
            'created_at' => $now, 'updated_at' => $now,
        ];
        $db->table('orders')->insert([
            $order(100, 'delivered', 'verified', 'data:image/png;base64,SLIP', 3),
            $order(101, 'pending_payment', 'slip_uploaded', 'submitted-without-image', null, 20),
            $order(102, 'pending_payment', 'pending', null, null, 9, 'BKK 1, Phnom Penh'),
        ]);
        $db->table('order_items')->insert([
            ['order_id' => 100, 'product_id' => 'genz-01', 'product_title' => 'Ring', 'price_usd' => 9, 'price_khr' => 36900, 'quantity' => 2, 'total_usd' => 18, 'total_khr' => 73800, 'created_at' => $now, 'updated_at' => $now],
            ['order_id' => 101, 'product_id' => 'genz-02', 'product_title' => 'Jeans', 'price_usd' => 20, 'price_khr' => 82000, 'quantity' => 1, 'total_usd' => 20, 'total_khr' => 82000, 'created_at' => $now, 'updated_at' => $now],
            ['order_id' => 102, 'product_id' => 'genz-01', 'product_title' => 'Ring', 'price_usd' => 9, 'price_khr' => 36900, 'quantity' => 1, 'total_usd' => 9, 'total_khr' => 36900, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $db->table('order_messages')->insert([
            ['order_id' => 100, 'user_id' => 2, 'from_staff' => false, 'body' => 'Hi', 'created_at' => $now, 'updated_at' => $now],
            ['order_id' => 100, 'user_id' => 3, 'from_staff' => true, 'body' => 'Hello', 'created_at' => $now, 'updated_at' => $now],
        ]);
        $db->table('product_reviews')->insert(['product_sku' => 'genz-01', 'order_id' => 100, 'user_id' => 2, 'rating' => 4, 'comment' => 'Nice', 'created_at' => $now, 'updated_at' => $now]);

        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'legacy', '--force' => true]), Artisan::output());

        // Roles and customers
        $this->assertSame(['admin', 'buyer', 'staff'], $db->table('users')->join('roles', 'roles.id', '=', 'users.role_id')->orderBy('users.id')->pluck('roles.name')->sort()->values()->all());
        $this->assertNull($db->table('users')->where('id', 3)->value('phone')); // '' became "no phone"
        // customers, product_details, order_statuses and permissions were merged into the tables that use them
        foreach (['customers', 'product_details', 'order_statuses', 'permissions'] as $merged) {
            $this->assertFalse($db->getSchemaBuilder()->hasTable($merged), $merged);
        }
        $this->assertSame(11, $db->table('role_permissions')->where('role_id', $db->table('roles')->where('name', 'admin')->value('id'))->count()); // every tick moved (Admin also gets the newer ones in code)
        $this->assertContains('manage_stock', $db->table('role_permissions')->where('role_id', $db->table('roles')->where('name', 'staff')->value('id'))->pluck('permission')->all());
        $this->assertFalse($db->getSchemaBuilder()->hasTable('user_settings')); // settings stay in the browser
        // Only what can't be worked out is stored: the promo discount comes from the code, there is no tax
        $this->assertFalse($db->getSchemaBuilder()->hasColumn('orders', 'discount_usd'));
        $this->assertFalse($db->getSchemaBuilder()->hasColumn('orders', 'tax_usd'));
        $this->assertFalse($db->getSchemaBuilder()->hasColumn('promo_codes', 'created_by'));
        // Plus / Pro / Max are site settings now (editable on the Website page), not a table
        $this->assertFalse($db->getSchemaBuilder()->hasTable('loyalty_tiers'));
        $this->assertSame(['100', '6', '500', '17', '18'], $db->table('site_settings')->whereIn('setting_key', [
            'membership.pro_min_spend_usd', 'membership.pro_discount_percent', 'membership.max_min_spend_usd', 'membership.max_discount_percent', 'membership.lapse_days',
        ])->orderByRaw("case setting_key when 'membership.pro_min_spend_usd' then 1 when 'membership.pro_discount_percent' then 2 when 'membership.max_min_spend_usd' then 3 when 'membership.max_discount_percent' then 4 else 5 end")->pluck('setting_value')->all());
        $this->assertSame(['setting_key', 'setting_value', 'updated_at'], $db->getSchemaBuilder()->getColumnListing('site_settings'));
        $this->assertSame('https://www.instagram.com/psaonline_kh', $db->table('site_settings')->where('setting_key', 'social.instagram')->value('setting_value')); // accounts are links
        $this->assertSame('St 240, Phnom Penh', $db->table('customer_addresses')->where('user_id', 2)->whereNull('archived_at')->value('address_line'));

        // Points are gone (membership comes from spending), and so are tables nothing needs
        foreach (['loyalty_transactions', 'user_notifications', 'showcase_products'] as $gone) {
            $this->assertFalse($db->getSchemaBuilder()->hasTable($gone), $gone);
        }

        // Catalog
        $this->assertSame(['/a.jpg', '/b.jpg', '/c.jpg'], $db->table('product_images')->where('product_id', 10)->orderBy('sort_order')->pluck('image_path')->all());
        $this->assertSame(1, $db->table('product_images')->where('product_id', 10)->where('is_primary', true)->count());
        $this->assertSame(['/j.jpg'], $db->table('product_images')->where('product_id', 11)->pluck('image_path')->all()); // empty gallery used the image
        $this->assertSame('Shiny', $db->table('products')->where('id', 10)->value('description'));
        // 1NF: the specification list and the material column became one row per fact
        $this->assertSame(['Metal' => 'Silver', 'Material' => 'Silver'], $db->table('product_specifications')->where('product_id', 10)->orderBy('sort_order')->pluck('value', 'name')->all());
        // Stock is the sum of the movements (product_stocks is gone)
        $this->assertFalse($db->getSchemaBuilder()->hasTable('product_stocks'));
        $this->assertEquals(20, $db->table('stock_movements')->where('product_id', 10)->sum('quantity_change'));
        $this->assertEquals(5, $db->table('products')->where('id', 10)->value('low_stock_threshold'));
        $jeansCategory = $db->table('categories')->where('id', $db->table('products')->where('id', 11)->value('category_id'))->first();
        $this->assertSame('Jeans', $jeansCategory->name);
        $this->assertSame('apparel', $db->table('categories')->where('id', $jeansCategory->parent_id)->value('slug'));
        $this->assertSame('jewelry', $db->table('categories')->where('id', $db->table('products')->where('id', 10)->value('category_id'))->value('slug')); // label = name, no sub-category

        // Orders, payments, history
        // The current status is the newest history row
        $statuses = collect([100, 101, 102])->map(fn ($id) => $db->table('order_status_history')->where('order_id', $id)->orderByDesc('id')->value('status'))->all();
        $this->assertSame(['delivered', 'pending_payment', 'pending_payment'], $statuses);
        // Who handles order 100 = who made its first change after "placed"
        $this->assertEquals(3, $db->table('order_status_history')->where('order_id', 100)->where('status', '!=', 'pending_payment')->orderBy('id')->value('changed_by'));
        // Each order points at the exact address it shipped to; addresses that only an old order used are archived
        foreach ([100 => 'St 240', 101 => 'St 240', 102 => 'BKK 1, Phnom Penh'] as $id => $line) {
            $address = $db->table('customer_addresses')->where('id', $db->table('orders')->where('id', $id)->value('address_id'))->first();
            $this->assertSame($line, $address->address_line);
            $this->assertSame('Dara Sok', $address->recipient_name);
            $this->assertNotNull($address->archived_at);
        }
        $this->assertNull($db->table('customer_addresses')->where('address_line', 'St 240, Phnom Penh')->value('archived_at')); // the address book entry stays
        foreach (['order_status_id', 'payment_method_id', 'handled_by', 'customer_name', 'subtotal_usd', 'total_usd'] as $column) {
            $this->assertFalse($db->getSchemaBuilder()->hasColumn('orders', $column), $column);
        }
        $this->assertSame(3, $db->table('orders')->where('user_id', 2)->count());
        $this->assertSame(['verified', 'slip_uploaded', 'pending'], $db->table('payments')->orderBy('order_id')->pluck('status')->all());
        $this->assertSame('data:image/png;base64,SLIP', $db->table('payments')->where('order_id', 100)->value('slip_url'));
        $this->assertNull($db->table('payments')->where('order_id', 101)->value('slip_url')); // placeholder text dropped
        $this->assertEquals(3, $db->table('payments')->where('order_id', 100)->value('verified_by'));
        $this->assertSame(2, $db->table('order_status_history')->where('order_id', 100)->count());
        $this->assertSame(1, $db->table('order_status_history')->where('order_id', 102)->count());

        // Items point to the product id, with the price agreed in that sale kept; the copied name is gone (2NF)
        $this->assertEquals(10, $db->table('order_items')->where('order_id', 100)->value('product_id'));
        $this->assertEquals(9, $db->table('order_items')->where('order_id', 100)->value('unit_price_usd'));
        $this->assertFalse($db->getSchemaBuilder()->hasColumn('order_items', 'product_title'));

        // Reviews and messages
        $review = $db->table('product_reviews')->first();
        $this->assertEquals(10, $review->product_id);
        $this->assertEquals(2, $review->user_id);
        $this->assertSame([2, 3], $db->table('order_messages')->orderBy('id')->pluck('sender_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame('data:image/png;base64,AA', $db->table('payment_methods')->value('qr_image_url'));
    }
}
