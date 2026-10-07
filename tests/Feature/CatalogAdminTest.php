<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Categories staff create and fill, and the home page showcase they choose. */
class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
    }

    private function user(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create(['name' => "{$role} {$n}", 'email' => "{$role}{$n}@example.com", 'password' => 'secret123', 'role' => $role]);
    }

    public function test_bootstrap_lists_categories_and_the_seeded_showcase(): void
    {
        $data = $this->getJson('/api/bootstrap')->assertOk()->json();

        $this->assertSame(['genz-25', 'genz-24', 'genz-08'], $data['showcase']);
        $apparel = collect($data['categories'])->firstWhere('slug', 'apparel');
        $this->assertNull($apparel['parentId']);
        $inApparel = collect($data['categories'])->filter(fn ($c) => $c['id'] === $apparel['id'] || $c['parentId'] === $apparel['id'])->sum('productCount');
        $this->assertSame(collect($data['products'])->where('category', 'apparel')->count(), $inApparel);
        $this->assertArrayHasKey('categoryId', $data['products'][0]);
    }

    public function test_staff_create_a_category_and_move_products_into_it(): void
    {
        $staff = $this->user('staff');

        $top = $this->actingAs($staff)->postJson('/api/admin/categories', ['name' => '  Back to   School '])
            ->assertCreated()->assertJsonPath('category.name', 'Back to School')->assertJsonPath('category.slug', 'back-to-school')
            ->json('category');
        $sub = $this->actingAs($staff)->postJson('/api/admin/categories', ['name' => 'Pencil Cases', 'parentId' => $top['id']])
            ->assertCreated()->assertJsonPath('category.parentId', $top['id'])->json('category');

        // Same name in the same place is refused; only two levels
        $this->actingAs($staff)->postJson('/api/admin/categories', ['name' => 'back to school'])->assertStatus(422);
        $this->actingAs($staff)->postJson('/api/admin/categories', ['name' => 'Tiny', 'parentId' => $sub['id']])->assertStatus(422);

        $this->actingAs($staff)->postJson('/api/admin/products/move', ['products' => ['genz-01', 'genz-02'], 'categoryId' => $sub['id']])
            ->assertOk()->assertJsonPath('moved', 2)
            ->assertJsonPath('products.0.category', 'back-to-school')
            ->assertJsonPath('products.0.categoryLabel', 'Pencil Cases');
        $this->assertSame(2, Product::where('category_id', $sub['id'])->count());

        // New products can go straight into it
        $id = $this->actingAs($staff)->postJson('/api/admin/products', ['title' => 'Star Pencil Case', 'categoryId' => $sub['id'], 'priceUSD' => 3.5, 'stock' => 4, 'description' => 'Holds 20 pens'])
            ->assertCreated()->assertJsonPath('product.categoryId', $sub['id'])->assertJsonPath('product.description', 'Holds 20 pens')->json('product.id');

        // The description is the product's product_details row (1:1)
        $product = Product::where('title', 'Star Pencil Case')->first();
        $this->assertSame('Holds 20 pens', \App\Models\ProductDetail::where('product_id', $product->id)->value('description'));
        $this->actingAs($staff)->patchJson("/api/admin/products/{$id}", ['description' => 'Holds 30 pens'])->assertOk()->assertJsonPath('product.description', 'Holds 30 pens');
        $this->assertSame(1, \App\Models\ProductDetail::where('product_id', $product->id)->count());

        // Rename keeps the slug (links keep working)
        $this->actingAs($staff)->patchJson("/api/admin/categories/{$top['id']}", ['name' => 'School Days'])
            ->assertOk()->assertJsonPath('category.name', 'School Days')->assertJsonPath('category.slug', 'back-to-school');
    }

    public function test_hidden_categories_hide_their_products_from_customers_only(): void
    {
        $staff = $this->user('staff');
        $hats = Category::where('slug', 'hats')->firstOrFail();
        $sku = Product::where('category_id', $hats->id)->value('sku')
            ?? Product::whereIn('category_id', $hats->children()->pluck('id'))->value('sku');
        $this->assertNotNull($sku);

        $this->actingAs($staff)->patchJson("/api/admin/categories/{$hats->id}", ['isActive' => false])->assertOk();
        $this->actingAsGuest();

        $this->assertNotContains($sku, array_column($this->getJson('/api/bootstrap')->json('products'), 'id'));
        $this->assertNotContains('hats', array_column($this->getJson('/api/bootstrap')->json('categories'), 'slug'));
        $this->assertContains($sku, array_column($this->actingAs($staff)->getJson('/api/bootstrap')->json('products'), 'id'));
    }

    public function test_only_admins_delete_and_only_empty_categories(): void
    {
        $staff = $this->user('staff');
        $admin = $this->user('admin');
        $socks = Category::where('slug', 'socks')->firstOrFail();
        $empty = Category::create(['name' => 'Empty', 'slug' => 'empty']);

        $this->actingAs($staff)->deleteJson("/api/admin/categories/{$empty->id}")->assertStatus(403);
        $this->actingAs($admin)->deleteJson("/api/admin/categories/{$socks->id}")->assertStatus(422);
        $this->actingAs($admin)->deleteJson("/api/admin/categories/{$empty->id}")->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $empty->id]);
    }

    public function test_staff_choose_the_showcase_and_customers_cannot(): void
    {
        $staff = $this->user('staff');
        $buyer = $this->user('buyer');

        $this->actingAs($buyer)->putJson('/api/admin/showcase', ['products' => ['genz-01']])->assertStatus(403);

        $this->actingAs($staff)->putJson('/api/admin/showcase', ['products' => ['genz-05', 'genz-01', 'genz-09']])
            ->assertOk()->assertJsonPath('showcase', ['genz-05', 'genz-01', 'genz-09']);
        $this->actingAsGuest();
        $this->assertSame(['genz-05', 'genz-01', 'genz-09'], $this->getJson('/api/bootstrap')->json('showcase'));

        $this->actingAs($staff)->putJson('/api/admin/showcase', ['products' => ['genz-01', 'genz-01']])->assertStatus(422);
        $this->actingAs($staff)->putJson('/api/admin/showcase', ['products' => ['nope']])->assertStatus(422);
        $this->actingAs($staff)->putJson('/api/admin/showcase', ['products' => array_fill(0, 7, 'x')])->assertStatus(422);

        // Archived products drop off the public showcase but stay chosen
        Product::where('sku', 'genz-01')->update(['status' => 'archived']);
        \App\Support\Storefront::forgetCatalog();
        $this->actingAsGuest();
        $this->assertSame(['genz-05', 'genz-09'], $this->getJson('/api/bootstrap')->json('showcase'));
        $this->assertSame(3, Product::whereNotNull('showcase_position')->count());
        $this->assertSame(['genz-05', 'genz-01', 'genz-09'], $this->actingAs($staff)->getJson('/api/bootstrap')->json('showcase'));

        $this->actingAs($staff)->putJson('/api/admin/showcase', ['products' => []])->assertOk();
        $this->assertSame(0, Product::whereNotNull('showcase_position')->count());
    }

    private function order(User $buyer, array $items): array
    {
        return $this->actingAs($buyer)->postJson('/api/orders', [
            'customerName' => 'Test Buyer', 'phone' => '+855 12 345 678', 'address' => 'St 240, Phnom Penh',
            'paymentMethod' => 'khqr', 'items' => $items,
        ])->assertCreated()->json('order');
    }

    public function test_the_sort_by_menu_starts_with_the_old_options_and_staff_can_change_it(): void
    {
        $staff = $this->user('staff');
        $start = $this->getJson('/api/bootstrap')->json('sortOptions');
        $this->assertSame(['Trending Picks', 'Price: Low to High', 'Price: High to Low', 'Top Rated'], array_column($start, 'label'));

        // rename + change what it sorts by
        $this->actingAs($staff)->patchJson("/api/admin/sort-options/{$start[3]['id']}", ['label' => 'Newest Drops', 'sortKey' => 'newest'])
            ->assertOk()->assertJsonPath('sortOption.label', 'Newest Drops')->assertJsonPath('sortOption.sortKey', 'newest');
        // a sort rule needs a valid key
        $this->actingAs($staff)->postJson('/api/admin/sort-options', ['label' => 'Odd', 'type' => 'sort', 'sortKey' => 'random'])->assertStatus(422);
        $this->actingAs($staff)->postJson('/api/admin/sort-options', ['label' => 'Odd', 'type' => 'sort'])->assertStatus(422);

        // reorder, hide
        $ids = array_reverse(array_column($start, 'id'));
        $this->actingAs($staff)->putJson('/api/admin/sort-options/order', ['ids' => $ids])->assertOk();
        $this->actingAs($staff)->patchJson("/api/admin/sort-options/{$start[1]['id']}", ['isActive' => false])->assertOk();
        $this->actingAsGuest();
        $public = $this->getJson('/api/bootstrap')->json('sortOptions');
        $this->assertSame(['Newest Drops', 'Price: High to Low', 'Trending Picks'], array_column($public, 'label'));

        // only admins delete
        $this->actingAs($staff)->deleteJson("/api/admin/sort-options/{$start[0]['id']}")->assertStatus(403);
        $this->actingAs($this->user('admin'))->deleteJson("/api/admin/sort-options/{$start[0]['id']}")->assertOk();
        $this->assertDatabaseMissing('sort_options', ['id' => $start[0]['id']]);
    }

    public function test_a_free_delivery_group_makes_the_whole_bag_ship_free(): void
    {
        $staff = $this->user('staff');
        $group = $this->actingAs($staff)->postJson('/api/admin/sort-options', [
            'label' => 'Free Delivery', 'type' => 'group', 'freeDelivery' => true, 'products' => ['genz-08'],
        ])->assertCreated()->assertJsonPath('sortOption.products', ['genz-08'])->assertJsonPath('sortOption.freeDelivery', true)->json('sortOption');

        $this->actingAsGuest();
        $products = collect($this->getJson('/api/bootstrap')->json('products'))->keyBy('id');
        $this->assertTrue($products['genz-08']['freeDelivery']);
        $this->assertFalse($products['genz-01']['freeDelivery']);

        $buyer = $this->user('buyer');
        $cheap = $products['genz-08']['priceUSD'];
        $this->assertLessThan(15, $cheap);

        // free item alone, and free item + normal item: no delivery fee
        $this->assertEquals(0, $this->order($buyer, [['id' => 'genz-08', 'quantity' => 1]])['deliveryUSD']);
        $mixed = $this->order($buyer, [['id' => 'genz-08', 'quantity' => 1], ['id' => 'genz-01', 'quantity' => 1]]);
        $this->assertEquals(0, $mixed['deliveryUSD']);
        // normal items under $15: $1.50
        $this->assertEquals(1.5, $this->order($buyer, [['id' => 'genz-04', 'quantity' => 1]])['deliveryUSD']);

        // switching free delivery off brings the fee back
        $this->actingAs($staff)->patchJson("/api/admin/sort-options/{$group['id']}", ['freeDelivery' => false])->assertOk();
        $this->assertEquals(1.5, $this->order($buyer, [['id' => 'genz-08', 'quantity' => 1]])['deliveryUSD']);

        // turning a group into a sort rule empties it
        $this->actingAs($staff)->patchJson("/api/admin/sort-options/{$group['id']}", ['type' => 'sort', 'sortKey' => 'name'])
            ->assertOk()->assertJsonPath('sortOption.products', []);
    }

}
