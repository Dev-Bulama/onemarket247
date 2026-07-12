<?php

use App\Models\Setting;
use Illuminate\Database\QueryException;

test('typed_value decodes according to the setting type', function () {
    $string = Setting::create(['key' => 'a.string', 'value' => 'hello', 'type' => 'string']);
    $bool = Setting::create(['key' => 'a.bool', 'value' => '1', 'type' => 'boolean']);
    $int = Setting::create(['key' => 'a.int', 'value' => '42', 'type' => 'integer']);
    $json = Setting::create(['key' => 'a.json', 'value' => json_encode(['x' => 1]), 'type' => 'json']);

    expect($string->typed_value)->toBe('hello')
        ->and($bool->typed_value)->toBeTrue()
        ->and($int->typed_value)->toBe(42)
        ->and($json->typed_value)->toBe(['x' => 1]);
});

test('setting keys are unique', function () {
    Setting::create(['key' => 'dup.key', 'value' => '1', 'type' => 'string']);

    expect(fn () => Setting::create(['key' => 'dup.key', 'value' => '2', 'type' => 'string']))
        ->toThrow(QueryException::class);
});
