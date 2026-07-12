<?php

use App\Models\Address;
use App\Models\User;

test('a user can update and delete their own address', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['addressable_type' => User::class, 'addressable_id' => $user->id]);

    expect($user->can('update', $address))->toBeTrue()
        ->and($user->can('delete', $address))->toBeTrue();
});

test('a user cannot update or delete someone else\'s address', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $address = Address::factory()->create(['addressable_type' => User::class, 'addressable_id' => $owner->id]);

    expect($stranger->can('update', $address))->toBeFalse()
        ->and($stranger->can('delete', $address))->toBeFalse();
});
