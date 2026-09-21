<?php

namespace App\Filament\Vendor\Resources\Products\RelationManagers;

use App\Support\Filament\MinorUnitsInput;
use App\Support\PriceDisplay;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix(PriceDisplay::baseCurrencyCode())
                    ->helperText('The selling price for this variation, e.g. 29.99.')
                    ->afterStateHydrated(MinorUnitsInput::hydrate())
                    ->dehydrateStateUsing(MinorUnitsInput::dehydrate()),
                TextInput::make('compare_at_price')
                    ->numeric()
                    ->prefix(PriceDisplay::baseCurrencyCode())
                    ->helperText('Optional "was" price shown struck through, e.g. 39.99.')
                    ->afterStateHydrated(MinorUnitsInput::hydrate())
                    ->dehydrateStateUsing(MinorUnitsInput::dehydrate()),
                TextInput::make('stock_quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                CheckboxList::make('attributeValues')
                    ->label('Attribute values')
                    ->relationship('attributeValues', 'value', fn ($query) => $query->whereHas('attribute', fn ($q) => $q->where('is_variation', true)))
                    ->columns(2),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('sku')
                    ->searchable(),
                TextColumn::make('attributeValues.value')
                    ->badge()
                    ->label('Attributes'),
                TextColumn::make('price')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                TextColumn::make('stock_quantity')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
