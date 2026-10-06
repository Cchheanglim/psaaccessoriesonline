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

        $old = array_values(array_filter(glob(database_path('migrations/*.php')), fn ($f) => ! str_contains($f, '2026_10_06_')));
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
        $order = fn (int $id, string $status, string $payment, ?string $slip, ?int $handler) => [
            'id' => $id, 'order_number' => "PSA-{$id}", 'user_id' => 2, 'handled_by' => $handler, 'customer_name' => 'Dara Sok', 'customer_phone' => '012',
            'delivery_address' => 'St 240', 'latitude' => 11.55, 'longitude' => 104.92, 'subtotal_usd' => 18, 'subtotal_khr' => 73800,
            'delivery_fee_usd' => 1.5, 'delivery_fee_khr' => 6150, 'total_usd' => 19.5, 'total_khr' => 79950, 'payment_method' => 'bakong_khqr',
            'payment_status' => $payment, 'order_status' => $status, 'payment_slip_url' => $slip, 'paid_at' => $payment === 'verified' ? $now : null,
            'created_at' => $now, 'updated_at' => $now,
        ];
        $db->table('orders')->insert([
            $order(100, 'delivered', 'verified', 'data:image/png;base64,SLIP', 3),
            $order(101, 'pending_payment', 'slip_uploaded', 'submitted-without-image', null),
            $order(102, 'pending_payment', 'pending', null, null),
        ]);
        $db->table('order_items')->insert([
            ['order_id' => 100, 'product_id' => 'genz-01', 'product_title' => 'Ring', 'price_usd' => 9, 'price_khr' => 36900, 'quantity' => 2, 'total_usd' => 18, 'total_khr' => 73800, 'created_at' => $now, 'updated_at' => $now],
            ['order_id' => 101, 'product_id' => 'genz-02', 'product_title' => 'Jeans', 'price_usd' => 20, 'price_khr' => 82000, 'quantity' => 1, 'total_usd' => 20, 'total_khr' => 82000, 'created_at' => $now, 'updated_at' => $now],
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
        $customer = $db->table('customers')->where('user_id', 2)->first();
        $this->assertNotNull($customer);
        $this->assertSame(1, $db->table('customers')->count()); // only the buyer
        $this->assertSame(3, $db->table('user_settings')->count());
        $this->assertSame('St 240, Phnom Penh', $db->table('customer_addresses')->where('customer_id', $customer->id)->value('address_line'));

        // Loyalty from the delivered order: floor($19.50) = 19 points
        $this->assertEquals(19, $customer->loyalty_points);
        $this->assertEquals(19.5, (float) $customer->total_spent_usd);

        // Catalog
        $this->assertSame(['/a.jpg', '/b.jpg', '/c.jpg'], $db->table('product_images')->where('product_id', 10)->orderBy('sort_order')->pluck('image_path')->all());
        $this->assertSame(1, $db->table('product_images')->where('product_id', 10)->where('is_primary', true)->count());
        $this->assertSame(['/j.jpg'], $db->table('product_images')->where('product_id', 11)->pluck('image_path')->all()); // empty gallery used the image
        $this->assertSame('Shiny', $db->table('product_details')->where('product_id', 10)->value('description'));
        $this->assertSame(['Metal' => 'Silver'], json_decode($db->table('product_details')->where('product_id', 10)->value('specifications'), true));
        $this->assertEquals(20, $db->table('product_stocks')->where('product_id', 10)->value('quantity_on_hand'));
        $this->assertEquals(20, $db->table('stock_movements')->where('product_id', 10)->sum('quantity_change'));
        $jeansCategory = $db->table('categories')->where('id', $db->table('products')->where('id', 11)->value('category_id'))->first();
        $this->assertSame('Jeans', $jeansCategory->name);
        $this->assertSame('apparel', $db->table('categories')->where('id', $jeansCategory->parent_id)->value('slug'));
        $this->assertSame('jewelry', $db->table('categories')->where('id', $db->table('products')->where('id', 10)->value('category_id'))->value('slug')); // label = name, no sub-category

        // Orders, payments, history
        $statuses = $db->table('orders')->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')->orderBy('orders.id')->pluck('order_statuses.code')->all();
        $this->assertSame(['delivered', 'pending_payment', 'pending_payment'], $statuses);
        $this->assertSame(3, $db->table('orders')->where('customer_id', $customer->id)->count());
        $this->assertSame(['verified', 'slip_uploaded', 'pending'], $db->table('payments')->orderBy('order_id')->pluck('status')->all());
        $this->assertSame('data:image/png;base64,SLIP', $db->table('payments')->where('order_id', 100)->value('slip_url'));
        $this->assertNull($db->table('payments')->where('order_id', 101)->value('slip_url')); // placeholder text dropped
        $this->assertEquals(3, $db->table('payments')->where('order_id', 100)->value('verified_by'));
        $this->assertSame(2, $db->table('order_status_history')->where('order_id', 100)->count());
        $this->assertSame(1, $db->table('order_status_history')->where('order_id', 102)->count());

        // Items now point to the product id, with the sell price kept
        $this->assertEquals(10, $db->table('order_items')->where('order_id', 100)->value('product_id'));
        $this->assertEquals(9, $db->table('order_items')->where('order_id', 100)->value('unit_price_usd'));

        // Reviews and messages
        $review = $db->table('product_reviews')->first();
        $this->assertEquals(10, $review->product_id);
        $this->assertEquals($customer->id, $review->customer_id);
        $this->assertSame([2, 3], $db->table('order_messages')->orderBy('id')->pluck('sender_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame('data:image/png;base64,AA', $db->table('payment_methods')->value('qr_image_url'));
    }
}
