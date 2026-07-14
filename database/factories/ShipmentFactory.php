<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\VendorOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'vendor_order_id' => VendorOrder::factory(),
            'shipping_carrier_id' => null,
            'pickup_station_id' => null,
            'tracking_number' => null,
            'status' => ShipmentStatus::Pending,
            'shipped_at' => null,
            'estimated_delivery_at' => null,
            'delivered_at' => null,
        ];
    }
}
