<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only from the admin panel — files are added/removed by the owning
 * vendor via the product form's staged upload; admins can only download
 * for review purposes.
 */
class DigitalFilesRelationManager extends RelationManager
{
    protected static string $relationship = 'digitalFiles';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state / 1024, 1).' KB' : '—'),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn ($record) => route('product-digital-files.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }
}
