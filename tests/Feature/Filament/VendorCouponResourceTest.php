<?php

use App\Enums\CouponType;
use App\Filament\Vendor\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Vendor\Resources\Coupons\Pages\EditCoupon;
use App\Models\Coupon;
use App\Models\Store;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    Filament::setCurrentPanel('vendor');
});

test('a vendor owner can load their coupon index and create a coupon', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $this->actingAs($vendor->user, 'vendor')->get('/vendor/coupons')->assertOk();

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(CreateCoupon::class)
        ->fillForm([
            'code' => 'welcome10',
            'type' => CouponType::Percentage->value,
            'value' => 10,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $coupon = Coupon::where('code', 'WELCOME10')->firstOrFail();
    expect($coupon->vendor_id)->toBe($vendor->id)
        ->and($coupon->value)->toBe(10);
});

test('a fixed-amount coupon value is stored in minor units', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(CreateCoupon::class)
        ->fillForm([
            'code' => 'flat5000',
            'type' => CouponType::Fixed->value,
            'value' => 5000,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $coupon = Coupon::where('code', 'FLAT5000')->firstOrFail();
    expect($coupon->value)->toBe(500000);
});

test('editing a fixed-amount coupon shows and saves the major-unit amount correctly', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $coupon = Coupon::factory()->fixed(500000)->create(['vendor_id' => $vendor->id, 'code' => 'EDITME']);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(EditCoupon::class, ['record' => $coupon->getRouteKey()])
        ->assertFormSet(['value' => 5000])
        ->fillForm(['value' => 7500])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($coupon->fresh()->value)->toBe(750000);
});

test('a vendor only sees their own coupons, not another vendors or a platform-wide one', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $ownCoupon = Coupon::factory()->create(['vendor_id' => $vendor->id, 'code' => 'MINE']);

    $otherVendor = Vendor::factory()->create();
    Store::factory()->for($otherVendor)->create();
    Coupon::factory()->create(['vendor_id' => $otherVendor->id, 'code' => 'THEIRS']);

    Coupon::factory()->create(['vendor_id' => null, 'code' => 'PLATFORMWIDE']);

    $this->actingAs($vendor->user, 'vendor')
        ->get('/vendor/coupons')
        ->assertOk()
        ->assertSee('MINE')
        ->assertDontSee('THEIRS')
        ->assertDontSee('PLATFORMWIDE');

    expect($ownCoupon->id)->not->toBeNull();
});

test('a vendor cannot edit another vendors coupon', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $otherVendor = Vendor::factory()->create();
    Store::factory()->for($otherVendor)->create();
    $otherCoupon = Coupon::factory()->create(['vendor_id' => $otherVendor->id]);

    $this->actingAs($vendor->user, 'vendor')
        ->get("/vendor/coupons/{$otherCoupon->id}/edit")
        ->assertNotFound();
});
