<?php

use App\Enums\StoreStaffStatus;
use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreStaff;
use Illuminate\Database\QueryException;

test('a store has many staff members', function () {
    $store = Store::factory()->create();
    $staff = StoreStaff::factory()->create(['store_id' => $store->id]);

    expect($store->staff)->toHaveCount(1)
        ->and($store->staff->first()->id)->toBe($staff->id);
});

test('isOpen reflects the store status', function () {
    $active = Store::factory()->create(['status' => StoreStatus::Active]);
    $vacation = Store::factory()->create(['status' => StoreStatus::Vacation]);

    expect($active->isOpen())->toBeTrue()
        ->and($vacation->isOpen())->toBeFalse();
});

test('store slug must be unique', function () {
    Store::factory()->create(['slug' => 'duplicate-store']);

    expect(fn () => Store::factory()->create(['slug' => 'duplicate-store']))
        ->toThrow(QueryException::class);
});

test('store staff status casts correctly', function () {
    $staff = StoreStaff::factory()->create(['status' => StoreStaffStatus::Invited]);

    expect($staff->fresh()->status)->toBe(StoreStaffStatus::Invited);
});
