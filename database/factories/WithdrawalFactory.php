<?php

namespace Database\Factories;

use App\Enums\WithdrawalStatus;
use App\Models\Vendor;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Withdrawal>
 */
class WithdrawalFactory extends Factory
{
    protected $model = Withdrawal::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'withdrawal_method_id' => WithdrawalMethod::factory(),
            'amount' => fake()->numberBetween(5000, 100000),
            'status' => WithdrawalStatus::Pending,
        ];
    }
}
