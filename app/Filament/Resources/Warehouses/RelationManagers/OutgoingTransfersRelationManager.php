<?php

namespace App\Filament\Resources\Warehouses\RelationManagers;

use App\Actions\Inventory\CancelStockTransferAction;
use App\Actions\Inventory\CompleteStockTransferAction;
use App\Actions\Inventory\DispatchStockTransferAction;
use App\Actions\Inventory\RequestStockTransferAction;
use App\Enums\StockTransferStatus;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * "Transfers" from this warehouse's perspective — requests it originates.
 * A transfer's full lifecycle (including the destination side) is also
 * visible platform-wide via the standalone StockTransferResource.
 */
class OutgoingTransfersRelationManager extends RelationManager
{
    protected static string $relationship = 'outgoingTransfers';

    protected static ?string $title = 'Transfers';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('toWarehouse.name')
                    ->label('To warehouse'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),
                TextColumn::make('requested_at')
                    ->dateTime(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->headerActions([
                Action::make('requestTransfer')
                    ->label('Request transfer')
                    ->schema(function (RelationManager $livewire) {
                        /** @var Warehouse $warehouse */
                        $warehouse = $livewire->getOwnerRecord();

                        return [
                            Select::make('to_warehouse_id')
                                ->label('Destination warehouse')
                                ->options(Warehouse::query()
                                    ->where('vendor_id', $warehouse->vendor_id)
                                    ->whereKeyNot($warehouse->id)
                                    ->pluck('name', 'id'))
                                ->required(),
                            Select::make('product_id')
                                ->label('Product')
                                ->options($warehouse->vendor->products()->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                            TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->minValue(1),
                            Textarea::make('notes'),
                        ];
                    })
                    ->action(function (array $data, RelationManager $livewire) {
                        /** @var Warehouse $warehouse */
                        $warehouse = $livewire->getOwnerRecord();
                        $toWarehouse = Warehouse::findOrFail($data['to_warehouse_id']);
                        $product = Product::findOrFail($data['product_id']);

                        app(RequestStockTransferAction::class)->handle(
                            $warehouse,
                            $toWarehouse,
                            [['sellable' => $product, 'quantity' => (int) $data['quantity']]],
                            auth()->user(),
                            $data['notes'] ?? null,
                        );

                        Notification::make()->title('Transfer requested')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('dispatch')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::Pending)
                    ->action(function (StockTransfer $record) {
                        app(DispatchStockTransferAction::class)->handle($record, auth()->user());
                        Notification::make()->title('Transfer dispatched')->success()->send();
                    }),
                Action::make('complete')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::InTransit)
                    ->action(function (StockTransfer $record) {
                        app(CompleteStockTransferAction::class)->handle($record, auth()->user());
                        Notification::make()->title('Transfer completed')->success()->send();
                    }),
                Action::make('cancel')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (StockTransfer $record) => in_array($record->status, [StockTransferStatus::Pending, StockTransferStatus::InTransit], true))
                    ->action(function (StockTransfer $record) {
                        app(CancelStockTransferAction::class)->handle($record, auth()->user());
                        Notification::make()->title('Transfer cancelled')->success()->send();
                    }),
            ]);
    }
}
