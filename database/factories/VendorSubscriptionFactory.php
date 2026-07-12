<?php

namespace Database\Factories;

use App\Enums\VendorSubscriptionStatus;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Models\VendorSubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorSubscription>
 */
class VendorSubscriptionFactory extends Factory
{
    protected $model = VendorSubscription::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'vendor_subscription_plan_id' => VendorSubscriptionPlan::factory(),
            'status' => VendorSubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => null,
        ];
    }
}
