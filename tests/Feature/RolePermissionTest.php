<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Staff & roles > Roles & permissions: admins create roles and tick what each may do; the server checks it. */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
        Role::flushCache();
    }

    private function user(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create(['name' => "Person {$n}", 'email' => "person{$n}@example.com", 'password' => 'secret123', 'role' => $role]);
    }

    private function order(): string
    {
        return $this->actingAs($this->user('buyer'))->postJson('/api/orders', [
            'customerName' => 'Dara Kim', 'phone' => '012 345 678', 'address' => 'St 63, Phnom Penh',
            'paymentMethod' => 'cod', 'items' => [['id' => 'genz-01', 'quantity' => 1]],
        ])->assertCreated()->json('order.id');
    }

    public function test_only_admins_see_and_change_roles(): void
    {
        $this->actingAs($this->user('staff'))->getJson('/api/admin/roles')->assertStatus(403);
        $this->actingAs($this->user('buyer'))->postJson('/api/admin/roles', ['name' => 'Packer', 'permissions' => []])->assertStatus(403);

        $res = $this->actingAs($this->user('admin'))->getJson('/api/admin/roles')->assertOk();
        $this->assertSame(['Admin', 'Staff', 'Buyer'], array_column($res->json('roles'), 'name'));
        $this->assertCount(12, $res->json('permissions'));
        $this->assertNotContains('adjust_loyalty', array_column($res->json('permissions'), 'name'));
        $admin = collect($res->json('roles'))->firstWhere('name', 'Admin');
        $this->assertTrue($admin['locked']);
        $this->assertCount(12, $admin['permissions']);
    }

    public function test_an_admin_creates_a_role_and_its_people_get_exactly_the_ticked_permissions(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->postJson('/api/admin/roles', [
            'name' => 'Order checker', 'description' => 'Checks and packs orders', 'permissions' => ['manage_orders'],
        ])->assertCreated()->assertJsonPath('roles.2.name', 'Order checker')->assertJsonPath('roles.2.permissions', ['manage_orders']);

        $this->actingAs($admin)->postJson('/api/admin/roles', ['name' => 'order CHECKER', 'permissions' => []])->assertStatus(422);
        $this->actingAs($admin)->postJson('/api/admin/roles', ['name' => 'X', 'permissions' => []])->assertStatus(422);
        $this->actingAs($admin)->postJson('/api/admin/roles', ['name' => 'Ghost', 'permissions' => ['fly']])->assertStatus(422);

        // the new role can be given to an account, which then gets into the portal with only that permission
        $id = $this->actingAs($admin)->postJson('/api/admin/users', [
            'name' => 'Packer One', 'email' => 'packer@example.com', 'password' => 'secret123', 'role' => 'Order checker',
        ])->assertCreated()->assertJsonPath('user.role', 'Order checker')->assertJsonPath('user.permissions', ['manage_orders'])->json('user.id');
        $packer = User::find($id);
        $this->assertTrue($packer->isStaff());

        $number = $this->order();
        $this->actingAs($packer)->patchJson("/api/admin/orders/{$number}", ['action' => 'verify'])->assertStatus(403);
        $this->actingAs($admin)->patchJson("/api/admin/orders/{$number}", ['action' => 'verify'])->assertOk();
        $this->actingAs($packer)->patchJson("/api/admin/orders/{$number}", ['action' => 'dispatch'])->assertOk();
        $this->actingAs($packer)->postJson('/api/admin/products', ['title' => 'Nope'])->assertStatus(403);
        $this->actingAs($packer)->getJson('/api/admin/conversations')->assertStatus(403);
        $this->get('/admin-orders')->assertOk();

        // ticking another permission works straight away
        $roleId = Role::idFor('order checker');
        $this->actingAs($admin)->patchJson("/api/admin/roles/{$roleId}", ['permissions' => ['manage_orders', 'manage_messages']])->assertOk();
        $this->actingAs($packer->fresh())->getJson('/api/admin/conversations')->assertOk();

        // a role with people in it can't be deleted; an empty one can
        $this->actingAs($admin)->deleteJson("/api/admin/roles/{$roleId}")->assertStatus(422);
        $this->actingAs($admin)->patchJson("/api/admin/users/{$id}", ['role' => 'Staff'])->assertOk();
        $this->actingAs($admin)->deleteJson("/api/admin/roles/{$roleId}")->assertOk();
        $this->assertNull(Role::find($roleId));
    }

    public function test_admin_and_buyer_are_locked_and_built_in_roles_stay(): void
    {
        $admin = $this->user('admin');
        foreach (['admin', 'buyer'] as $name) {
            $this->actingAs($admin)->patchJson('/api/admin/roles/'.Role::idFor($name), ['permissions' => []])->assertStatus(422);
        }
        $staff = Role::idFor('staff');
        $this->actingAs($admin)->patchJson("/api/admin/roles/{$staff}", ['name' => 'Crew'])->assertStatus(422);
        $this->actingAs($admin)->deleteJson("/api/admin/roles/{$staff}")->assertStatus(422);

        // untick "Edit products & shop" for Staff: staff can no longer add products, admins still can
        $this->actingAs($admin)->patchJson("/api/admin/roles/{$staff}", ['permissions' => ['manage_orders', 'verify_payments']])->assertOk();
        $product = ['title' => 'Star Clip', 'category' => 'Hair Clips', 'priceUSD' => 3, 'stock' => 5];
        $this->actingAs($this->user('staff'))->postJson('/api/admin/products', $product)->assertStatus(403);
        $this->actingAs($admin)->postJson('/api/admin/products', $product)->assertCreated();
    }

    public function test_people_who_manage_accounts_cannot_hand_out_more_than_they_have(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->postJson('/api/admin/roles', ['name' => 'Team lead', 'permissions' => ['manage_users', 'manage_orders']])->assertCreated();
        $this->actingAs($admin)->postJson('/api/admin/roles', ['name' => 'Packer', 'permissions' => ['manage_orders']])->assertCreated();
        $lead = $this->user('team lead');
        $staff = $this->user('staff');

        $this->actingAs($lead)->getJson('/api/bootstrap')->assertOk()->assertJsonCount(5, 'roles');
        $this->actingAs($lead)->patchJson("/api/admin/users/{$staff->id}", ['role' => 'Packer'])->assertOk();
        $this->actingAs($lead)->patchJson("/api/admin/users/{$staff->id}", ['role' => 'Staff'])->assertStatus(403); // Staff can do more
        $this->actingAs($lead)->patchJson("/api/admin/users/{$staff->id}", ['role' => 'Admin'])->assertStatus(403);
        $this->actingAs($lead)->patchJson("/api/admin/users/{$admin->id}", ['status' => 'Suspended'])->assertStatus(403);
        $this->actingAs($lead)->patchJson("/api/admin/users/{$lead->id}", ['role' => 'Packer'])->assertStatus(422);
    }
}
