<?php

namespace App\Filament\Resources\AgentApplications\RelationManagers;

use App\Models\AgentDocument;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * View/download only — an agent application is approved or rejected as a
 * whole (see AgentApplicationsTable's approve/reject actions), unlike
 * vendor documents which can also be reviewed one at a time.
 */
class AgentDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('file_path')
                    ->label('Document')
                    ->formatStateUsing(fn () => 'Open document')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (AgentDocument $record) => route('agent-documents.download', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
