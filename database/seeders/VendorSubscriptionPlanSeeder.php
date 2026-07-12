<?php

namespace Database\Seeders;

use App\Enums\BillingPeriod;
use App\Models\VendorSubscriptionPlan;
use Illuminate\Database\Seeder;

class VendorSubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Free plan for new vendors getting started.',
                'price' => 0,
                'billing_period' => BillingPeriod::Monthly,
                'max_products' => 25,
                'features' => ['Up to 25 products', 'Standard commission rate', 'Email support'],
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'For vendors ready to scale their catalog.',
                'price' => 2900,
                'billing_period' => BillingPeriod::Monthly,
                'max_products' => 500,
                'commission_rate_override' => 8.00,
                'features' => ['Up to 500 products', 'Reduced commission rate', 'Priority support'],
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited catalog for high-volume vendors.',
                'price' => 9900,
                'billing_period' => BillingPeriod::Monthly,
                'max_products' => null,
                'commission_rate_override' => 5.00,
                'features' => ['Unlimited products', 'Lowest commission rate', 'Dedicated account manager'],
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $data) {
            VendorSubscriptionPlan::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
