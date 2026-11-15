<?php

namespace App\Filament\Vendor\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use App\Support\Filament\MinorUnitsInput;
use App\Support\PriceDisplay;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CouponForm
{
    /**
     * A reactive Select's live state via Get can come back as either the
     * raw backing value or the enum case itself depending on context —
     * normalizing here avoids every call site having to guess which.
     */
    private static function typeFrom(Get $get): ?CouponType
    {
        $value = $get('type');

        return match (true) {
            $value instanceof CouponType => $value,
            $value !== null => CouponType::from($value),
            default => null,
        };
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true)
                ->helperText('Shoppers type this at checkout. Stored and matched in uppercase.'),
            Select::make('type')
                ->options(CouponType::class)
                ->default(CouponType::Percentage)
                ->live()
                ->required(),
            // Deliberately no per-field hydrate/dehydrate conversion here —
            // this field's meaning (plain percentage vs. a currency amount)
            // depends on the sibling 'type' field, and converting it as
            // part of the whole form submission (see CreateCoupon/EditCoupon)
            // is far more predictable than relying on cross-field reads
            // inside this component's own hydrate/dehydrate hooks.
            TextInput::make('value')
                ->label(fn (Get $get) => self::typeFrom($get) === CouponType::Fixed ? 'Amount off' : 'Percentage off')
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(fn (Get $get) => self::typeFrom($get) === CouponType::Percentage ? 100 : null)
                ->suffix(fn (Get $get) => self::typeFrom($get) === CouponType::Percentage ? '%' : PriceDisplay::baseCurrencyCode()),
            TextInput::make('minimum_spend')
                ->label('Minimum spend (optional)')
                ->numeric()
                ->prefix(PriceDisplay::baseCurrencyCode())
                ->helperText('Leave blank for no minimum.')
                ->afterStateHydrated(MinorUnitsInput::hydrate())
                ->dehydrateStateUsing(MinorUnitsInput::dehydrate()),
            DateTimePicker::make('starts_at')
                ->label('Starts at (optional)'),
            DateTimePicker::make('expires_at')
                ->label('Expires at (optional)'),
            Toggle::make('is_active')
                ->default(true),
        ]);
    }
}
