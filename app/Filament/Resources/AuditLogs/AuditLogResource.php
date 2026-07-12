<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only: no form, no create/edit pages, no delete action — see
 * AuditLogsTable for why.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Security';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('logs.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
