<?php

namespace App\Filament\Resources\ShippingZones\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * A location row with only country_id set matches every state/city in
 * that country; adding state_id/city_id narrows it — see
 * App\Actions\Shipping\ResolveShippingZoneAction for the matching order.
 */
class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('country_id')
                ->relationship('country', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('state_id')
                ->label('State (optional — narrows the match)')
                ->relationship('state', 'name')
                ->searchable()
                ->preload(),
            Select::make('city_id')
                ->label('City (optional — narrows the match further)')
                ->relationship('city', 'name')
                ->searchable()
                ->preload(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('country.name')
                    ->label('Country'),
                TextColumn::make('state.name')
                    ->label('State')
                    ->placeholder('Any'),
                TextColumn::make('city.name')
                    ->label('City')
                    ->placeholder('Any'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
