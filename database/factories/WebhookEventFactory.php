<?php

namespace Database\Factories;

use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition(): array
    {
        return [
            'gateway' => 'paystack',
            'event_id' => fake()->unique()->uuid(),
            'payload' => ['event' => 'charge.success'],
            'processed_at' => now(),
        ];
    }
}
