<?php

namespace Database\Factories;

use App\Models\PackingSlip;
use App\Models\VendorOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackingSlip>
 */
class PackingSlipFactory extends Factory
{
    protected $model = PackingSlip::class;

    public function definition(): array
    {
        return [
            'vendor_order_id' => VendorOrder::factory(),
            'generated_at' => now(),
        ];
    }
}
