<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorWallet>
 */
class VendorWalletFactory extends Factory
{
    protected $model = VendorWallet::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'pending_balance' => 0,
            'available_balance' => 0,
            'reserved_balance' => 0,
            'withdrawn_balance' => 0,
        ];
    }
}
