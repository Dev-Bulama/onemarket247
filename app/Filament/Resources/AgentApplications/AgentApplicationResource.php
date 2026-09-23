<?php

namespace App\Filament\Resources\AgentApplications;

use App\Filament\Resources\AgentApplications\Pages\ListAgentApplications;
use App\Filament\Resources\AgentApplications\Pages\ViewAgentApplication;
use App\Filament\Resources\AgentApplications\RelationManagers\AgentDocumentsRelationManager;
use App\Filament\Resources\AgentApplications\Tables\AgentApplicationsTable;
use App\Models\AgentApplication;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only + action-driven, like VendorApplicationResource: applications
 * are never created or free-form edited by admins, only reviewed
 * (approve/reject) via table/page actions — see
 * App\Actions\Agent\{Approve,Reject}AgentApplicationAction.
 */
class AgentApplicationResource extends Resource
{
    protected static ?string $model = AgentApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Agents';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return AgentApplicationsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Applicant')
                ->columns(3)
                ->schema([
                    TextEntry::make('full_name'),
                    TextEntry::make('email'),
                    TextEntry::make('phone')->placeholder('—'),
                ]),
            Section::make('Location')
                ->columns(3)
                ->schema([
                    TextEntry::make('country.name')->placeholder('—'),
                    TextEntry::make('state.name')->placeholder('—'),
                    TextEntry::make('city.name')->placeholder('—'),
                    TextEntry::make('address')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Identity')
                ->columns(2)
                ->schema([
                    TextEntry::make('identity_type')->placeholder('—'),
                    TextEntry::make('identity_number')->placeholder('—'),
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Review')
                ->columns(2)
                ->schema([
                    TextEntry::make('status')->badge(),
                    TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            AgentDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgentApplications::route('/'),
            'view' => ViewAgentApplication::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
