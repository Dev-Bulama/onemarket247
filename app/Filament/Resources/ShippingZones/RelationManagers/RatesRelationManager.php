<?php

namespace App\Filament\Resources\ShippingZones\RelationManagers;

use App\Enums\ShippingRateType;
use App\Support\PriceDisplay;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RatesRelationManager extends RelationManager
{
    protected static string $relationship = 'rates';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Select::make('shipping_class_id')
                ->label('Shipping class (leave blank to apply to all classes)')
                ->relationship('shippingClass', 'name')
                ->searchable()
                ->preload(),
            Select::make('rate_type')
                ->options(ShippingRateType::class)
                ->required()
                ->live(),
            TextInput::make('base_amount')
                ->label('Base amount')
                ->numeric()
                ->required()
                ->default(0)
                ->helperText('Minor currency units (e.g. cents).')
                ->visible(fn (Get $get) => $get('rate_type') !== ShippingRateType::Free),
            TextInput::make('per_kg_amount')
                ->label('Per kilogram amount')
                ->numeric()
                ->helperText('Minor currency units (e.g. cents), added per kg of shipment weight.')
                ->visible(fn (Get $get) => $get('rate_type') === ShippingRateType::PerWeight),
            TextInput::make('free_shipping_min_amount')
                ->label('Free shipping threshold (optional)')
                ->numeric()
                ->helperText('Order subtotal (minor units) at or above which shipping becomes free, regardless of rate type.'),
            Toggle::make('is_active')
                ->default(true),
            TextInput::make('sort_order')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('shippingClass.name')
                    ->label('Class')
                    ->placeholder('All classes'),
                TextColumn::make('rate_type')
                    ->badge(),
                TextColumn::make('base_amount')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                TextColumn::make('per_kg_amount')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100)
                    ->placeholder('—'),
                TextColumn::make('free_shipping_min_amount')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100)
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
