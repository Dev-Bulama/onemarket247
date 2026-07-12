<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only by design: audit_logs is an insert-only ledger (see
 * docs/architecture/10-security-architecture.md §6) — no edit/delete
 * actions are registered here or on the resource.
 */
class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Actor')
                    ->searchable()
                    ->placeholder('System'),
                TextColumn::make('action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('auditable_type')
                    ->label('Subject type')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : null)
                    ->searchable(),
                TextColumn::make('auditable_id')
                    ->label('Subject ID')
                    ->numeric(),
                TextColumn::make('ip_address')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_agent')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('before')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state) : null)
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('after')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state) : null)
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->options(fn () => AuditLog::query()->distinct()->pluck('action', 'action')->all()),
            ]);
    }
}
