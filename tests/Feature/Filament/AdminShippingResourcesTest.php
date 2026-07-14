<?php

use App\Enums\ShipmentStatus;
use App\Enums\ShippingRateType;
use App\Enums\VendorOrderStatus;
use App\Filament\Resources\ShippingZones\Pages\EditShippingZone;
use App\Filament\Resources\VendorOrders\Pages\ViewVendorOrder;
use App\Filament\Resources\VendorOrders\RelationManagers\ShipmentsRelationManager;
use App\Models\Country;
use App\Models\ShippingCarrier;
use App\Models\ShippingClass;
use App\Models\ShippingZone;
use App\Models\User;
use App\Models\VendorOrder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function shippingAdminUser(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load all four shipping resource index and create pages', function () {
    $admin = shippingAdminUser();

    foreach (['shipping-zones', 'shipping-classes', 'shipping-carriers', 'pickup-stations'] as $slug) {
        $this->actingAs($admin, 'admin')->get("/admin/{$slug}")->assertOk();
        $this->actingAs($admin, 'admin')->get("/admin/{$slug}/create")->assertOk();
    }
});

test('an admin without shipping.manage cannot access the shipping zones resource', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/shipping-zones')->assertForbidden();
});

test('an admin can add a location and a rate to a shipping zone via its relation managers', function () {
    $admin = shippingAdminUser();
    $zone = ShippingZone::factory()->create();
    $country = Country::factory()->create();
    $class = ShippingClass::factory()->create();

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(EditShippingZone::class, ['record' => $zone->getRouteKey()])
        ->assertOk();

    $zone->locations()->create(['country_id' => $country->id]);
    $zone->rates()->create([
        'shipping_class_id' => $class->id,
        'name' => 'Standard',
        'rate_type' => ShippingRateType::Flat,
        'base_amount' => 500,
    ]);

    expect($zone->fresh()->locations)->toHaveCount(1)
        ->and($zone->fresh()->rates)->toHaveCount(1);
});

test('an admin can create a shipment from the vendor order Shipments relation manager and it advances the order to shipped', function () {
    $admin = shippingAdminUser();
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::ReadyForPickup]);
    $carrier = ShippingCarrier::factory()->create();

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(ShipmentsRelationManager::class, [
            'ownerRecord' => $vendorOrder,
            'pageClass' => ViewVendorOrder::class,
        ])
        ->callTableAction('createShipment', data: [
            'shipping_carrier_id' => $carrier->id,
            'tracking_number' => 'TRACK999',
        ]);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Shipped);
    $shipment = $vendorOrder->fresh()->shipments->first();
    expect($shipment->status)->toBe(ShipmentStatus::Shipped)
        ->and($shipment->tracking_number)->toBe('TRACK999');
});

test('recording shipment events from the relation manager advances the vendor order through out_for_delivery to delivered', function () {
    $admin = shippingAdminUser();
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::ReadyForPickup]);
    $carrier = ShippingCarrier::factory()->create();

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(ShipmentsRelationManager::class, [
            'ownerRecord' => $vendorOrder,
            'pageClass' => ViewVendorOrder::class,
        ])
        ->callTableAction('createShipment', data: [
            'shipping_carrier_id' => $carrier->id,
            'tracking_number' => 'TRACK999',
        ]);

    $shipment = $vendorOrder->fresh()->shipments->first();

    Livewire::actingAs($admin, 'admin')
        ->test(ShipmentsRelationManager::class, [
            'ownerRecord' => $vendorOrder->fresh(),
            'pageClass' => ViewVendorOrder::class,
        ])
        ->callTableAction('recordEvent', $shipment, data: [
            'status' => ShipmentStatus::OutForDelivery->value,
            'location' => 'Local depot',
        ]);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::OutForDelivery);

    Livewire::actingAs($admin, 'admin')
        ->test(ShipmentsRelationManager::class, [
            'ownerRecord' => $vendorOrder->fresh(),
            'pageClass' => ViewVendorOrder::class,
        ])
        ->callTableAction('recordEvent', $shipment->fresh(), data: [
            'status' => ShipmentStatus::Delivered->value,
            'location' => 'Front door',
        ]);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Delivered);
});

test('the createShipment action is hidden once the vendor order is no longer ready for pickup', function () {
    $admin = shippingAdminUser();
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::Processing]);

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(ShipmentsRelationManager::class, [
            'ownerRecord' => $vendorOrder,
            'pageClass' => ViewVendorOrder::class,
        ])
        ->assertTableActionHidden('createShipment');
});
