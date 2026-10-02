<?php

namespace Tests\Feature;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_a_single_active_support_conversation(): void
    {
        $customer = User::factory()->create(['role' => Role::Traveler]);

        $this->actingAs($customer)
            ->get(route('support.index'))
            ->assertOk()
            ->assertSeeText('Hello! Welcome to Havenedge Tourlink Support. How can we help you today?');

        $this->get(route('support.index'))->assertOk();

        $this->assertSame(1, $customer->supportConversations()->count());
    }

    public function test_customer_message_notifies_active_admin_and_admin_reply_notifies_customer(): void
    {
        $customer = User::factory()->create(['name' => 'Customer One', 'role' => Role::Traveler]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $conversation = SupportConversation::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($customer)
            ->post(route('support.messages.store', $conversation), ['message' => 'I need help with my booking.'])
            ->assertRedirect(route('support.index', ['conversation' => $conversation->id]));

        $message = $conversation->messages()->firstOrFail();
        $this->assertSame(SupportMessage::SENDER_CUSTOMER, $message->sender_type);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'support_message:'.$conversation->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.support.messages.store', $conversation), ['message' => 'We are checking this for you.'])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type' => 'support_message:'.$conversation->id,
        ]);
        $this->assertSame(SupportConversation::STATUS_PENDING, $conversation->fresh()->status);
    }

    public function test_opening_conversation_marks_incoming_messages_read_for_each_side(): void
    {
        $customer = User::factory()->create(['role' => Role::Traveler]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $conversation = SupportConversation::factory()->create(['user_id' => $customer->id]);
        $customerMessage = SupportMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $customer->id,
            'sender_type' => SupportMessage::SENDER_CUSTOMER,
        ]);

        $this->actingAs($admin)->get(route('admin.support.show', $conversation))->assertOk();
        $this->assertNotNull($customerMessage->fresh()->read_at);

        $adminMessage = SupportMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'sender_type' => SupportMessage::SENDER_ADMIN,
        ]);

        $this->actingAs($customer)->get(route('support.index'))->assertOk();
        $this->assertNotNull($adminMessage->fresh()->read_at);
    }

    public function test_customer_cannot_access_or_send_to_another_customers_conversation(): void
    {
        $owner = User::factory()->create(['role' => Role::Traveler]);
        $otherCustomer = User::factory()->create(['role' => Role::Traveler]);
        $conversation = SupportConversation::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($otherCustomer)
            ->get(route('support.messages.index', $conversation))
            ->assertNotFound();

        $this->post(route('support.messages.store', $conversation), ['message' => 'Private message'])
            ->assertNotFound();
    }

    public function test_only_admin_can_use_admin_support_dashboard(): void
    {
        $customer = User::factory()->create(['role' => Role::Traveler]);

        $this->actingAs($customer)
            ->get(route('admin.support.index'))
            ->assertForbidden();
    }

    public function test_closed_conversation_rejects_customer_messages_until_admin_reopens_it(): void
    {
        $customer = User::factory()->create(['role' => Role::Traveler]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $conversation = SupportConversation::factory()->create([
            'user_id' => $customer->id,
            'status' => SupportConversation::STATUS_CLOSED,
        ]);

        $this->actingAs($customer)
            ->post(route('support.messages.store', $conversation), ['message' => 'Can I continue?'])
            ->assertStatus(409);

        $this->actingAs($admin)
            ->patch(route('admin.support.status', $conversation), ['status' => SupportConversation::STATUS_OPEN])
            ->assertRedirect();

        $this->actingAs($customer)
            ->post(route('support.messages.store', $conversation), ['message' => 'Can I continue?'])
            ->assertRedirect();

        $this->assertDatabaseHas('support_messages', [
            'conversation_id' => $conversation->id,
            'message' => 'Can I continue?',
        ]);
    }
}
