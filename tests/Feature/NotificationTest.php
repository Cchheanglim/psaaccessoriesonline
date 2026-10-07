<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
    }

    private function user(string $role, string $name): User
    {
        return User::create(['name' => $name, 'email' => strtolower(str_replace(' ', '', $name)).'@example.com', 'password' => 'secret123', 'role' => $role]);
    }

    private function placeOrder(User $buyer): string
    {
        return $this->actingAs($buyer)->postJson('/api/orders', [
            'customerName' => $buyer->name, 'phone' => '012 345 678', 'address' => 'St 240, Phnom Penh',
            'paymentMethod' => 'khqr', 'items' => [['id' => 'genz-08', 'quantity' => 1]],
        ])->assertCreated()->json('order.id');
    }

    public function test_new_orders_and_slips_notify_every_staff_member_until_someone_approves(): void
    {
        $buyer = $this->user('buyer', 'Dara Sok');
        $sokha = $this->user('staff', 'Sokha Lim');
        $admin = $this->user('admin', 'Vanna Chea');

        $number = $this->placeOrder($buyer);
        $this->actingAs($buyer)->postJson("/api/orders/{$number}/slip", ['slip' => null])->assertOk();

        foreach ([$sokha, $admin] as $staff) {
            $this->actingAs($staff)->getJson('/api/notifications')->assertOk()
                ->assertJsonPath('unread', 2)
                ->assertJsonPath('items.0.type', 'slip_uploaded')
                ->assertJsonPath('items.1.title', "New order {$number}");
        }
        $this->actingAs($buyer)->getJson('/api/notifications')->assertJsonPath('unread', 0);
    }

    public function test_approver_becomes_the_contact_and_the_customer_is_told(): void
    {
        $buyer = $this->user('buyer', 'Dara Sok');
        $sokha = $this->user('staff', 'Sokha Lim');
        $other = $this->user('staff', 'Rithy Pen');
        $number = $this->placeOrder($buyer);

        $this->actingAs($sokha)->patchJson("/api/admin/orders/{$number}", ['action' => 'verify'])
            ->assertOk()->assertJsonPath('order.handledBy', 'Sokha Lim');

        $this->actingAs($buyer)->getJson('/api/notifications')
            ->assertJsonPath('items.0.title', 'Payment confirmed')
            ->assertJsonPath('items.0.link', "order-detail.html?order={$number}");
        $this->assertStringContainsString('Sokha approved', $this->actingAs($buyer)->getJson('/api/notifications')->json('items.0.body'));

        // After approval the customer's messages go to Sokha only
        $this->actingAs($buyer)->postJson("/api/orders/{$number}/messages", ['body' => 'Can you deliver at 6pm?'])->assertCreated();
        $this->actingAs($sokha)->getJson('/api/notifications')->assertJsonPath('items.0.type', 'message')->assertJsonPath('unreadMessages', 1);
        $this->actingAs($other)->getJson('/api/notifications')->assertJsonPath('unreadMessages', 0);
    }

    public function test_opening_a_thread_marks_it_read_and_replies_notify_the_customer(): void
    {
        $buyer = $this->user('buyer', 'Dara Sok');
        $sokha = $this->user('staff', 'Sokha Lim');
        $number = $this->placeOrder($buyer);
        $this->actingAs($sokha)->patchJson("/api/admin/orders/{$number}", ['action' => 'verify'])->assertOk();

        $this->actingAs($buyer)->postJson("/api/orders/{$number}/messages", ['body' => 'Hello!'])->assertCreated();
        $this->actingAs($sokha)->getJson('/api/admin/conversations')->assertOk()
            ->assertJsonPath('conversations.0.orderId', $number)
            ->assertJsonPath('conversations.0.unread', 1)
            ->assertJsonPath('conversations.0.mine', true);

        $this->actingAs($sokha)->getJson("/api/orders/{$number}/messages")->assertOk();
        $this->actingAs($sokha)->getJson('/api/admin/conversations')->assertJsonPath('conversations.0.unread', 0);
        $this->actingAs($sokha)->getJson('/api/notifications')->assertJsonPath('unreadMessages', 0);

        $this->actingAs($sokha)->postJson("/api/orders/{$number}/messages", ['body' => 'Hi Dara, 6pm works.'])->assertCreated();
        $this->actingAs($buyer)->getJson('/api/notifications')->assertJsonPath('unreadMessages', 1)->assertJsonPath('items.0.type', 'message');
        $this->actingAs($buyer)->getJson('/api/bootstrap')->assertJsonPath('orders.0.unreadReplies', 1);

        // Opening the bell: the browser remembers when, and sends it back as ?since=
        $seen = now()->addSecond()->toIso8601String();
        $this->actingAs($buyer)->getJson('/api/notifications?since='.urlencode($seen))->assertJsonPath('unread', 0)->assertJsonPath('items.0.read', true);
        $this->assertSame(0, \Illuminate\Support\Facades\Schema::hasTable('user_notifications') ? 1 : 0); // nothing stored for the bell
    }

    public function test_custom_roles_get_the_notifications_their_permissions_cover(): void
    {
        $admin = $this->user('admin', 'Vanna Chea');
        $this->actingAs($admin)->postJson('/api/admin/roles', ['name' => 'Dispatcher', 'permissions' => ['manage_orders']])->assertCreated();
        $this->actingAs($admin)->postJson('/api/admin/roles', ['name' => 'Packer', 'permissions' => ['manage_products']])->assertCreated();
        $dispatcher = $this->user('dispatcher', 'Dispatcher Dara');
        $packer = $this->user('packer', 'Packer Sok');

        $number = $this->placeOrder($this->user('buyer', 'Dara Sok'));
        $this->actingAs($dispatcher)->getJson('/api/notifications')->assertJsonPath('items.0.title', "New order {$number}");
        $this->actingAs($packer)->getJson('/api/notifications')->assertJsonPath('unread', 0)->assertJsonCount(0, 'items');
    }

    public function test_only_staff_see_the_inbox_and_guests_have_no_bell(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
        $this->actingAs($this->user('buyer', 'Dara Sok'))->getJson('/api/admin/conversations')->assertStatus(403);
    }
}
