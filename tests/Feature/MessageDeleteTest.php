<?php

namespace Tests\Feature;

use App\Models\OrderMessage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Customers delete their own messages; staff with "Customer messages" delete any message or the whole chat. */
class MessageDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
    }

    private function user(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role).' Person', 'email' => $email, 'password' => 'secret123', 'role' => $role]);
    }

    /** An order with one message from the customer and one reply from staff. */
    private function chat(): array
    {
        $buyer = $this->user('buyer', 'dara@example.com');
        $staff = $this->user('staff', 'sokha@example.com');
        $number = $this->actingAs($buyer)->postJson('/api/orders', [
            'customerName' => 'Dara Kim', 'phone' => '012 345 678', 'address' => 'St 63, Phnom Penh',
            'paymentMethod' => 'cod', 'items' => [['id' => 'genz-01', 'quantity' => 1]],
        ])->assertCreated()->json('order.id');
        $mine = $this->actingAs($buyer)->postJson("/api/orders/{$number}/messages", ['body' => 'Hi, after 5pm please'])->assertCreated()->json('messages.0.id');
        $reply = $this->actingAs($staff)->postJson("/api/orders/{$number}/messages", ['body' => 'Sure!'])->assertCreated()->json('messages.1.id');

        return [$buyer, $staff, $number, $mine, $reply];
    }

    public function test_a_customer_deletes_their_own_message_but_not_the_shops_or_the_whole_chat(): void
    {
        [$buyer, , $number, $mine, $reply] = $this->chat();

        $thread = $this->actingAs($buyer)->getJson("/api/orders/{$number}/messages")->assertOk();
        $this->assertSame([true, false], array_column($thread->json('messages'), 'canDelete'));
        $thread->assertJsonPath('canDeleteChat', false);

        $this->actingAs($buyer)->deleteJson("/api/orders/{$number}/messages/{$reply}")->assertStatus(403);
        $this->actingAs($buyer)->deleteJson("/api/orders/{$number}/messages")->assertStatus(403);
        $this->actingAs($buyer)->deleteJson("/api/orders/{$number}/messages/{$mine}")->assertOk()->assertJsonCount(1, 'messages');
        $this->assertNull(OrderMessage::find($mine)); // gone for both sides

        // Someone else's order is not theirs to touch
        $other = $this->user('buyer', 'vanna@example.com');
        $this->actingAs($other)->deleteJson("/api/orders/{$number}/messages/{$reply}")->assertStatus(403);
    }

    public function test_staff_delete_any_message_or_the_whole_chat_and_roles_without_messages_cannot(): void
    {
        [$buyer, $staff, $number, $mine] = $this->chat();

        $this->actingAs($staff)->getJson("/api/orders/{$number}/messages")->assertJsonPath('canDeleteChat', true)->assertJsonPath('messages.0.canDelete', true);
        $this->actingAs($staff)->deleteJson("/api/orders/{$number}/messages/{$mine}")->assertOk()->assertJsonCount(1, 'messages');
        $this->actingAs($staff)->deleteJson("/api/orders/{$number}/messages")->assertOk()->assertJsonCount(0, 'messages');
        $this->assertSame(0, OrderMessage::count());

        // A role without "Customer messages" can't delete anything
        $admin = $this->user('admin', 'boss@example.com');
        $this->actingAs($admin)->postJson('/api/admin/roles', ['name' => 'Rider', 'permissions' => ['manage_orders']])->assertCreated();
        $rider = $this->user('rider', 'rider@example.com');
        $id = $this->actingAs($buyer)->postJson("/api/orders/{$number}/messages", ['body' => 'Hello again'])->json('messages.0.id');
        $this->actingAs($rider)->deleteJson("/api/orders/{$number}/messages/{$id}")->assertStatus(403);
        $this->actingAs($rider)->deleteJson("/api/orders/{$number}/messages")->assertStatus(403);
    }
}
