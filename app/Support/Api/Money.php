<?php

namespace App\Support\Api;

use App\Support\PriceDisplay;

/**
 * Every API resource that surfaces a price uses this shape rather than a
 * bare integer, so mobile never has to know the minor-unit/formatting
 * convention documented on Product.price — it gets the raw amount (for
 * calculations), the ISO code, and an already-formatted string (for
 * display) in one object.
 */
class Money
{
    /**
     * @return array{amount: int, currency: string, formatted: string}|null
     */
    public static function make(?int $minorAmount): ?array
    {
        if ($minorAmount === null) {
            return null;
        }

        return [
            'amount' => $minorAmount,
            'currency' => PriceDisplay::baseCurrencyCode(),
            'formatted' => PriceDisplay::format($minorAmount),
        ];
    }
}
