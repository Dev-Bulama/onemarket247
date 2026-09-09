<?php

namespace App\Filament\Resources\LegalPages\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegalPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('key')->badge(),
                TextColumn::make('updated_at')->dateTime()->label('Last updated'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
