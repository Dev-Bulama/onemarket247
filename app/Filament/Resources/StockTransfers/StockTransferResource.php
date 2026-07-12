<?php

namespace App\Filament\Resources\StockTransfers;

use App\Filament\Resources\StockTransfers\Pages\ListStockTransfers;
use App\Filament\Resources\StockTransfers\Pages\ViewStockTransfer;
use App\Filament\Resources\StockTransfers\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\StockTransfers\Tables\StockTransfersTable;
use App\Models\StockTransfer;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only + action-driven, like VendorApplicationResource: transfers are
 * requested via App\Actions\Inventory\RequestStockTransferAction (see
 * WarehouseResource's OutgoingTransfersRelationManager), never created or
 * free-form edited here — this resource is the platform-wide oversight
 * view, with dispatch/complete/cancel actions on the view page.
 */
class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return StockTransfersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Transfer')
                ->columns(3)
                ->schema([
                    TextEntry::make('fromWarehouse.name')->label('From warehouse'),
                    TextEntry::make('toWarehouse.name')->label('To warehouse'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('requester.name')->label('Requested by')->placeholder('—'),
                    TextEntry::make('requested_at')->dateTime(),
                    TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockTransfers::route('/'),
            'view' => ViewStockTransfer::route('/{record}'),
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

    public static function canDelete($record): bool
    {
        return false;
    }
}
