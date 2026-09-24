<?php

namespace App\Filament\Resources\Agents;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\Agents\Pages\CreateAgent;
use App\Filament\Resources\Agents\Pages\EditAgent;
use App\Filament\Resources\Agents\Pages\ListAgents;
use App\Filament\Resources\Agents\RelationManagers\SettlementsRelationManager;
use App\Filament\Resources\Agents\RelationManagers\WalletTransactionsRelationManager;
use App\Filament\Resources\Agents\Schemas\AgentForm;
use App\Filament\Resources\Agents\Tables\AgentsTable;
use App\Models\Agent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The approved-agent roster — most agents arrive here via
 * AgentApplicationResource's "approve" action, but an admin can also add
 * one directly (e.g. an internal hire), since an Agent has no User/Store/
 * Warehouse provisioning cascade to worry about, unlike Vendor.
 */
class AgentResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'agents.manage';

    protected static ?string $model = Agent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Agents';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return AgentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SettlementsRelationManager::class,
            WalletTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgents::route('/'),
            'create' => CreateAgent::route('/create'),
            'edit' => EditAgent::route('/{record}/edit'),
        ];
    }
}
