<?php

use App\Actions\Shipping\CreateShipmentAction;
use App\Enums\VendorOrderStatus;
use App\Filament\Vendor\Pages\Shipments;
use App\Models\ShippingCarrier;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('a vendor can load the shipments page and create a shipment for their own ready-for-pickup order', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $vendorOrder = VendorOrder::factory()->create(['vendor_id' => $vendor->id, 'status' => VendorOrderStatus::ReadyForPickup]);
    $carrier = ShippingCarrier::factory()->create();

    $this->actingAs($vendor->user, 'vendor')->get('/vendor/shipments')->assertOk();

    Filament::setCurrentPanel('vendor');

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Shipments::class)
        ->callAction('createShipment', data: [
            'vendor_order_id' => $vendorOrder->id,
            'shipping_carrier_id' => $carrier->id,
            'tracking_number' => 'VTRACK1',
        ]);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Shipped);
    expect($vendorOrder->fresh()->shipments()->first()->tracking_number)->toBe('VTRACK1');
});

test('a vendor only sees their own shipments on the shipments page', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $otherVendor = Vendor::factory()->create();

    $mine = VendorOrder::factory()->create(['vendor_id' => $vendor->id, 'status' => VendorOrderStatus::ReadyForPickup]);
    $theirs = VendorOrder::factory()->create(['vendor_id' => $otherVendor->id, 'status' => VendorOrderStatus::ReadyForPickup]);

    app(CreateShipmentAction::class)->handle($mine, null, 'MINE123', null, null);
    app(CreateShipmentAction::class)->handle($theirs, null, 'THEIRS456', null, null);

    Filament::setCurrentPanel('vendor');

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Shipments::class)
        ->assertCanSeeTableRecords($mine->fresh()->shipments)
        ->assertCanNotSeeTableRecords($theirs->fresh()->shipments);
});

test('a vendor cannot create a shipment for another vendor\'s order, even by submitting its id directly', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $otherVendor = Vendor::factory()->create();
    $theirs = VendorOrder::factory()->create(['vendor_id' => $otherVendor->id, 'status' => VendorOrderStatus::ReadyForPickup]);

    Filament::setCurrentPanel('vendor');

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Shipments::class)
        ->callAction('createShipment', data: [
            'vendor_order_id' => $theirs->id,
            'tracking_number' => 'SHOULD-NOT-EXIST',
        ])
        ->assertHasErrors(['mountedActions.0.data.vendor_order_id']);

    expect($theirs->fresh()->status)->toBe(VendorOrderStatus::ReadyForPickup);
    expect($theirs->fresh()->shipments)->toHaveCount(0);
});
