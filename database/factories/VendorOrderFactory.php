<?php

namespace Database\Factories;

use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorOrder>
 */
class VendorOrderFactory extends Factory
{
    protected $model = VendorOrder::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(2000, 50000);

        return [
            'order_id' => Order::factory(),
            'vendor_id' => Vendor::factory(),
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => $subtotal,
            'status' => VendorOrderStatus::PendingPayment,
        ];
    }
}
