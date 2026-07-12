<?php

namespace Database\Factories;

use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->vendorOwner(),
            'business_name' => fake()->company(),
            'registration_number' => fake()->bothify('REG-########'),
            'tax_identification_number' => fake()->bothify('TIN-########'),
            'status' => VendorStatus::Approved,
            'commission_rate' => fake()->randomFloat(2, 5, 20),
            'is_verified' => true,
            'is_featured' => false,
            'bank_name' => fake()->company().' Bank',
            'bank_account_name' => fake()->name(),
            'bank_account_number' => fake()->bankAccountNumber(),
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VendorStatus::Pending,
            'is_verified' => false,
            'approved_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VendorStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }
}
