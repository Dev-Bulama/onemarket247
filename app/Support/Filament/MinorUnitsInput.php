<?php

namespace App\Support\Filament;

use Closure;
use Filament\Forms\Components\TextInput;

/**
 * Every *_price column in this app stores minor currency units (cents) —
 * see App\Support\PriceDisplay's docblock — but a human typing into a
 * plain numeric TextInput expects to type "29.99", not "2999". Applying
 * both closures below to a price TextInput shows/accepts major units
 * while the underlying column keeps storing minor units, so vendor/admin
 * price entry always ends up with the same on-disk value the storefront,
 * API, and mobile app already expect and display correctly.
 *
 * Without this, a vendor typing "29.99" into an unconverted TextInput
 * gets it stored as-is into an unsignedBigInteger column (truncated to
 * 29) — a product meant to cost $29.99 silently becomes $0.29 everywhere
 * it's actually sold, while a table column showing the same raw value
 * through ->money() (which expects minor units) renders it as $29.00.
 */
class MinorUnitsInput
{
    public static function hydrate(): Closure
    {
        return function (TextInput $component, mixed $state): void {
            $component->state($state !== null ? $state / 100 : null);
        };
    }

    public static function dehydrate(): Closure
    {
        return fn (mixed $state) => filled($state) ? (int) round(((float) $state) * 100) : null;
    }
}
