<?php

namespace Database\Factories;

use App\Models\SupportConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportConversation>
 */
class SupportConversationFactory extends Factory
{
    protected $model = SupportConversation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'assigned_admin_id' => null,
            'booking_id' => null,
            'status' => SupportConversation::STATUS_OPEN,
            'last_message_at' => null,
            'last_read_by_customer_at' => null,
            'last_read_by_admin_at' => null,
        ];
    }
}
