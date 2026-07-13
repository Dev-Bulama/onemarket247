<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only — a customer manages their own address book from
 * /account/addresses; admins can only view it here.
 */
class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('label'),
                TextColumn::make('full_name'),
                TextColumn::make('address_line_1')
                    ->label('Address'),
                TextColumn::make('city.name')
                    ->placeholder('—'),
                IconColumn::make('is_default_shipping')
                    ->label('Default shipping')
                    ->boolean(),
                IconColumn::make('is_default_billing')
                    ->label('Default billing')
                    ->boolean(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
