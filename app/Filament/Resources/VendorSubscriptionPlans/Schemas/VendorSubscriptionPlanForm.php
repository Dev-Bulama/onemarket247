<?php

namespace App\Filament\Resources\VendorSubscriptionPlans\Schemas;

use App\Enums\BillingPeriod;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VendorSubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Minor currency units (e.g. cents). 0 = free plan.'),
                Select::make('billing_period')
                    ->options(BillingPeriod::class)
                    ->default('monthly')
                    ->required(),
                TextInput::make('max_products')
                    ->numeric()
                    ->helperText('Leave blank for unlimited.'),
                TextInput::make('commission_rate_override')
                    ->numeric()
                    ->suffix('%')
                    ->helperText('Leave blank to use the vendor\'s own commission rate.'),
                KeyValue::make('features')
                    ->columnSpanFull(),
                Toggle::make('is_default')
                    ->helperText('New applicants are assigned this plan unless they choose another.'),
                Toggle::make('is_active')
                    ->default(true),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
