<?php

namespace Tests\Feature;

use App\Models\OrderMessage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Customers delete their own messages; staff with "Customer messages" delete any message or the whole chat.
 * The row stays with deleted_at and deleted_by, and the chat shows who deleted it. */
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
        $thread = $this->actingAs($buyer)->deleteJson("/api/orders/{$number}/messages/{$mine}")->assertOk()->json('messages');
        // The text is gone for both sides; the row stays with when and who
        $this->assertNull(OrderMessage::find($mine));
        $row = OrderMessage::withTrashed()->find($mine);
        $this->assertNotNull($row->deleted_at);
        $this->assertSame($buyer->id, $row->deleted_by);
        $this->assertSame([true, null, true, false], [$thread[0]['deleted'], $thread[0]['body'], $thread[0]['deletedByMe'], $thread[0]['canDelete']]);
        $this->assertSame('Sure!', $thread[1]['body']);
        $this->assertSame(1, $this->actingAs($buyer)->getJson('/api/bootstrap')->json('orders.0.messageCount'));

        // Someone else's order is not theirs to touch
        $other = $this->user('buyer', 'vanna@example.com');
        $this->actingAs($other)->deleteJson("/api/orders/{$number}/messages/{$reply}")->assertStatus(403);
    }

    public function test_staff_delete_any_message_or_the_whole_chat_and_roles_without_messages_cannot(): void
    {
        [$buyer, $staff, $number, $mine] = $this->chat();

        $this->actingAs($staff)->getJson("/api/orders/{$number}/messages")->assertJsonPath('canDeleteChat', true)->assertJsonPath('messages.0.canDelete', true);
        $this->actingAs($staff)->deleteJson("/api/orders/{$number}/messages/{$mine}")->assertOk();
        $this->actingAs($staff)->deleteJson("/api/orders/{$number}/messages")->assertOk()
            ->assertJsonCount(1, 'messages') // both shown as one "2 messages deleted by ..." line
            ->assertJsonPath('messages.0.count', 2)->assertJsonPath('messages.0.deletedBy', 'Staff Person');
        $this->assertSame(0, OrderMessage::count());
        $this->assertSame([$staff->id, $staff->id], OrderMessage::onlyTrashed()->orderBy('id')->pluck('deleted_by')->all());

        // The customer sees the shop's name, not the staff member's
        $this->actingAs($buyer)->getJson("/api/orders/{$number}/messages")->assertJsonPath('messages.0.deletedBy', 'PsaOnline')->assertJsonPath('messages.0.body', null);

        // A role without "Customer messages" can't delete anything
        $admin = $this->user('admin', 'boss@example.com');
        $this->actingAs($admin)->postJson('/api/admin/roles', ['name' => 'Packer', 'permissions' => ['manage_orders']])->assertCreated();
        $packer = $this->user('packer', 'packer@example.com');
        $id = collect($this->actingAs($buyer)->postJson("/api/orders/{$number}/messages", ['body' => 'Hello again'])->json('messages'))->last()['id'];
        $this->actingAs($packer)->deleteJson("/api/orders/{$number}/messages/{$id}")->assertStatus(403);
        $this->actingAs($packer)->deleteJson("/api/orders/{$number}/messages")->assertStatus(403);
    }

    public function test_a_quiet_chat_is_deleted_after_7_days_and_removed_for_good_15_days_later(): void
    {
        [$buyer, , $number] = $this->chat();

        // 6 days later the chat is still there
        $this->travel(6)->days();
        $this->assertSame([0, 0], OrderMessage::cleanUp());

        // 7 days after the last message: deleted automatically (no deleted_by)
        $this->travel(2)->days();
        $this->assertSame([2, 0], OrderMessage::cleanUp());
        $this->assertSame([null, null], OrderMessage::onlyTrashed()->pluck('deleted_by')->all());
        $this->actingAs($buyer)->getJson("/api/orders/{$number}/messages")
            ->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.count', 2)->assertJsonPath('messages.0.deletedBy', null);

        // A new message starts a fresh chat; the deleted ones are removed for good after 15 more days
        $this->travel(14)->days();
        $this->actingAs($buyer)->postJson("/api/orders/{$number}/messages", ['body' => 'Is it on the way?'])->assertCreated();
        $this->assertSame([0, 0], OrderMessage::cleanUp());
        $this->travel(2)->days();
        $this->assertSame([0, 2], OrderMessage::cleanUp());
        $this->assertSame(1, OrderMessage::withTrashed()->count());

        // Admins can change both numbers
        $admin = $this->user('admin', 'boss@example.com');
        $this->actingAs($admin)->putJson('/api/admin/site-settings', ['settings' => ['chat.auto_delete_days' => '1']])->assertOk();
        $this->travel(2)->days();
        $this->assertSame([1, 0], OrderMessage::cleanUp());
    }
}
