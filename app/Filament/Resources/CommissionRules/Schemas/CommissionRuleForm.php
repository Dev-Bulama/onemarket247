<?php

namespace App\Filament\Resources\CommissionRules\Schemas;

use App\Enums\CommissionScopeType;
use App\Enums\CommissionType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CommissionRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('scope_type')
                ->options(CommissionScopeType::class)
                ->required()
                ->live(),
            Select::make('category_id')
                ->relationship('category', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn (Get $get) => $get('scope_type') === CommissionScopeType::Category),
            Select::make('product_id')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn (Get $get) => $get('scope_type') === CommissionScopeType::Product),
            Select::make('vendor_id')
                ->relationship('vendor', 'business_name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn (Get $get) => $get('scope_type') === CommissionScopeType::Vendor),
            Select::make('subscription_plan_id')
                ->relationship('subscriptionPlan', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn (Get $get) => $get('scope_type') === CommissionScopeType::SubscriptionPlan),
            Select::make('rate_type')
                ->options(CommissionType::class)
                ->required()
                ->live(),
            TextInput::make('rate_value')
                ->numeric()
                ->required()
                ->minValue(0)
                ->prefix(fn (Get $get) => $get('rate_type') === CommissionType::Fixed ? '$' : null)
                ->suffix(fn (Get $get) => $get('rate_type') === CommissionType::Percentage ? '%' : null)
                ->maxValue(fn (Get $get) => $get('rate_type') === CommissionType::Percentage ? 100 : null)
                ->helperText(fn (Get $get) => $get('rate_type') === CommissionType::Fixed ? 'A flat dollar amount, e.g. 5.00 for $5.00.' : null),
            Toggle::make('is_active')
                ->default(true),
        ]);
    }
}
