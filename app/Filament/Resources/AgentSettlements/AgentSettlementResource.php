<?php

namespace App\Filament\Resources\AgentSettlements;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\AgentSettlements\Pages\CreateAgentSettlement;
use App\Filament\Resources\AgentSettlements\Pages\ListAgentSettlements;
use App\Filament\Resources\AgentSettlements\Pages\ViewAgentSettlement;
use App\Filament\Resources\AgentSettlements\Schemas\AgentSettlementForm;
use App\Filament\Resources\AgentSettlements\Tables\AgentSettlementsTable;
use App\Models\AgentSettlement;
use App\Support\PriceDisplay;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Admin-initiated equivalent of WithdrawalResource — an Agent has no
 * account to request its own payout (see Agent's docblock), so unlike
 * Withdrawal this resource allows creation, always on the agent's behalf.
 * Once created, a settlement only changes through
 * App\Actions\Settlement\* (approve/reject/mark-paid), never a free-form
 * edit form.
 */
class AgentSettlementResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'agents.manage';

    protected static ?string $model = AgentSettlement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Agents';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return AgentSettlementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgentSettlementsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Settlement')
                ->columns(3)
                ->schema([
                    TextEntry::make('agent.full_name')->label('Agent'),
                    TextEntry::make('amount')->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('createdBy.name')->label('Created by')->placeholder('—'),
                    TextEntry::make('reviewer.name')->label('Reviewed by')->placeholder('—'),
                    TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                    TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                    TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgentSettlements::route('/'),
            'create' => CreateAgentSettlement::route('/create'),
            'view' => ViewAgentSettlement::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
