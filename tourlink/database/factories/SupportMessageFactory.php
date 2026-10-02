<?php

namespace Database\Factories;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportMessage>
 */
class SupportMessageFactory extends Factory
{
    protected $model = SupportMessage::class;

    public function definition(): array
    {
        return [
            'conversation_id' => SupportConversation::factory(),
            'sender_id' => User::factory(),
            'sender_type' => SupportMessage::SENDER_CUSTOMER,
            'message' => fake()->sentence(),
            'read_at' => null,
            'created_at' => now(),
        ];
    }
}
