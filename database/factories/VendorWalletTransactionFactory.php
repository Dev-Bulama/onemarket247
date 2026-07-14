<?php

namespace Database\Factories;

use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Models\VendorWallet;
use App\Models\VendorWalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorWalletTransaction>
 */
class VendorWalletTransactionFactory extends Factory
{
    protected $model = VendorWalletTransaction::class;

    public function definition(): array
    {
        return [
            'vendor_wallet_id' => VendorWallet::factory(),
            'type' => WalletTransactionType::SaleCreditPending,
            'balance_bucket' => WalletBalanceBucket::Pending,
            'amount' => fake()->numberBetween(1000, 50000),
        ];
    }
}
