<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'user_id' => User::factory(),
            'product_id' => null,
            'subject' => null,
            'last_message_at' => null,
            'closed_at' => null,
            'closed_by' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(['closed_at' => now(), 'closed_by' => User::factory()->admin()]);
    }
}
