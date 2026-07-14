<?php

namespace Database\Factories;

use App\Enums\WithdrawalMethodType;
use App\Models\Vendor;
use App\Models\WithdrawalMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithdrawalMethod>
 */
class WithdrawalMethodFactory extends Factory
{
    protected $model = WithdrawalMethod::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'type' => WithdrawalMethodType::BankTransfer,
            'bank_name' => fake()->company().' Bank',
            'account_name' => fake()->name(),
            'account_number' => fake()->numerify('##########'),
            'is_default' => true,
        ];
    }
}
