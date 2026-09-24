<?php

namespace Database\Factories;

use App\Models\DeliveryPartner;
use App\Models\DeliveryRequest;
use App\Models\DeliveryRequestNotification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeliveryRequestNotification>
 */
class DeliveryRequestNotificationFactory extends Factory
{
    protected $model = DeliveryRequestNotification::class;

    public function definition(): array
    {
        return [
            'delivery_request_id' => DeliveryRequest::factory(),
            'delivery_partner_id' => DeliveryPartner::factory(),
            'token' => Str::random(48),
        ];
    }
}
