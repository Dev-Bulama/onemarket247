<?php

namespace Database\Factories;

use App\Enums\VendorApplicationStatus;
use App\Models\VendorApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorApplication>
 */
class VendorApplicationFactory extends Factory
{
    protected $model = VendorApplication::class;

    public function definition(): array
    {
        $storeName = fake()->unique()->company();

        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'business_name' => $storeName,
            'store_name' => $storeName,
            'store_slug' => Str::slug($storeName).'-'.fake()->unique()->numberBetween(1, 99999),
            'address' => fake()->address(),
            'store_description' => fake()->paragraph(),
            'bank_name' => fake()->company().' Bank',
            'bank_account_name' => fake()->name(),
            'bank_account_number' => fake()->bankAccountNumber(),
            'status' => VendorApplicationStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => VendorApplicationStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => VendorApplicationStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
            'reviewed_at' => now(),
        ]);
    }
}
