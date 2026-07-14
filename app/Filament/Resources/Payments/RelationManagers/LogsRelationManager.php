<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only — every row is written by App\Actions\Payment\* as the audit
 * trail of a gateway interaction, never edited directly.
 */
class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Gateway logs';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('direction')
            ->columns([
                TextColumn::make('direction')
                    ->badge(),
                TextColumn::make('payload')
                    ->limit(80)
                    ->formatStateUsing(fn (?array $state) => json_encode($state)),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
