<?php

namespace App\Filament\Resources\TaxClasses\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
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
            Select::make('country_id')
                ->label('Country')
                ->relationship('country', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->live(),
            Select::make('state_id')
                ->label('State (optional, narrows to this country)')
                ->relationship('state', 'name', fn ($query, $get) => $query->where('country_id', $get('country_id')))
                ->searchable()
                ->preload(),
            Select::make('city_id')
                ->label('City (optional, narrows to this state)')
                ->relationship('city', 'name', fn ($query, $get) => $query->where('state_id', $get('state_id')))
                ->searchable()
                ->preload(),
            TextInput::make('postal_code')
                ->label('Postal code (optional, most specific)')
                ->maxLength(255),
            TextInput::make('rate_percent')
                ->label('Rate (%)')
                ->numeric()
                ->required()
                ->default(0),
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
                TextColumn::make('country.name')
                    ->label('Country'),
                TextColumn::make('state.name')
                    ->label('State')
                    ->placeholder('—'),
                TextColumn::make('city.name')
                    ->label('City')
                    ->placeholder('—'),
                TextColumn::make('postal_code')
                    ->placeholder('—'),
                TextColumn::make('rate_percent')
                    ->label('Rate')
                    ->suffix('%'),
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
