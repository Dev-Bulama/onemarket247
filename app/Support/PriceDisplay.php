<?php

namespace App\Support;

use App\Actions\Currency\ConvertCurrencyAction;
use App\Enums\CurrencySymbolPosition;
use App\Models\Currency;
use Illuminate\Support\Facades\App;

/**
 * Browse-time price formatting: converts a minor-unit amount (always
 * stored in the store's default/settlement currency — see Product.price)
 * into the customer's chosen display currency and formats it using that
 * currency's own symbol/decimal/separator conventions. The chosen
 * currency is kept as a container singleton (reset per request/test by
 * the framework itself) rather than a bare static property, so it can
 * never leak between requests or test cases the way a plain static would.
 */
class PriceDisplay
{
    public static function setDisplayCurrency(Currency $currency): void
    {
        App::instance('display.currency', $currency);
    }

    /**
     * The ISO code every monetary column (Product.price, Order totals,
     * VendorWallet balances, etc.) is actually stored in — used by admin/
     * vendor panel money columns so their currency symbol follows whichever
     * currency is flagged default instead of being hardcoded.
     */
    public static function baseCurrencyCode(): string
    {
        return static::baseCurrency()?->code ?? 'USD';
    }

    public static function format(int $minorAmount): string
    {
        $base = static::baseCurrency();
        $display = App::bound('display.currency') ? App::make('display.currency') : $base;

        if (! $base || ! $display) {
            return '$'.number_format($minorAmount / 100, 2);
        }

        $converted = $display->id === $base->id
            ? $minorAmount
            : app(ConvertCurrencyAction::class)->handle($minorAmount, $base, $display);

        return static::formatMinor($converted, $display);
    }

    private static function baseCurrency(): ?Currency
    {
        return Currency::where('is_default', true)->first();
    }

    private static function formatMinor(int $minorAmount, Currency $currency): string
    {
        $major = $minorAmount / (10 ** $currency->decimal_places);
        $formatted = number_format($major, $currency->decimal_places, $currency->decimal_separator, $currency->thousand_separator);

        return $currency->symbol_position === CurrencySymbolPosition::After
            ? $formatted.$currency->symbol
            : $currency->symbol.$formatted;
    }
}
