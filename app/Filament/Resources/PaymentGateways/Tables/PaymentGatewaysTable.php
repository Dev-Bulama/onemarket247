<?php

namespace App\Filament\Resources\PaymentGateways\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentGatewaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code'),
                TextColumn::make('name'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('updated_at')->dateTime()->label('Last updated'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
