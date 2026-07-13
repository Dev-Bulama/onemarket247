<?php

use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;

function makeVendorOrderWithSlip(?Vendor $vendor = null): VendorOrder
{
    $vendor ??= Vendor::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['vendor_id' => $vendor->id]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id]);
    $vendorOrder->packingSlip()->create(['generated_at' => now()]);

    return $vendorOrder;
}

test('the owning vendor can download their own packing slip', function () {
    $vendor = Vendor::factory()->create();
    $vendorOrder = makeVendorOrderWithSlip($vendor);

    $this->actingAs($vendor->user, 'vendor')
        ->get(route('packing-slips.download', $vendorOrder))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('a different vendor cannot download someone elses packing slip', function () {
    $vendorOrder = makeVendorOrderWithSlip();
    $otherVendor = Vendor::factory()->create();

    // BelongsToVendorScope hides the other vendor's row entirely from route
    // model binding while the vendor guard is authenticated, so this 404s
    // rather than reaching the policy to produce a 403 — the same
    // enumeration-safe behaviour as every other vendor-scoped resource.
    $this->actingAs($otherVendor->user, 'vendor')
        ->get(route('packing-slips.download', $vendorOrder))
        ->assertNotFound();
});

test('an admin with orders.view can download any packing slip', function () {
    (new RolePermissionSeeder)->run();

    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $vendorOrder = makeVendorOrderWithSlip();

    $this->actingAs($admin, 'admin')
        ->get(route('packing-slips.download', $vendorOrder))
        ->assertOk();
});

test('a guest is redirected to login', function () {
    $vendorOrder = makeVendorOrderWithSlip();

    $this->get(route('packing-slips.download', $vendorOrder))->assertRedirect();
});
