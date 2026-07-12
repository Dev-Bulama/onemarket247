<?php

use App\Models\Currency;
use App\Models\Language;

test('creating a new default language unsets the previous default', function () {
    $first = Language::factory()->create(['is_default' => true]);
    $second = Language::factory()->create(['is_default' => true]);

    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue();
});

test('updating an existing language to default unsets siblings', function () {
    $first = Language::factory()->create(['is_default' => true]);
    $second = Language::factory()->create(['is_default' => false]);

    $second->update(['is_default' => true]);

    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue();
});

test('saving a non-default language does not touch the existing default', function () {
    $default = Language::factory()->create(['is_default' => true]);
    $other = Language::factory()->create(['is_default' => false]);

    $other->update(['native_name' => 'Updated']);

    expect($default->fresh()->is_default)->toBeTrue();
});

test('creating a new default currency unsets the previous default', function () {
    $first = Currency::factory()->create(['is_default' => true]);
    $second = Currency::factory()->create(['is_default' => true]);

    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue();
});

test('updating an existing currency to default unsets siblings', function () {
    $first = Currency::factory()->create(['is_default' => true]);
    $second = Currency::factory()->create(['is_default' => false]);

    $second->update(['is_default' => true]);

    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue();
});
