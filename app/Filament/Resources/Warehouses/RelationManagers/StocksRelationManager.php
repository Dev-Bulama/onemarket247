<?php

namespace App\Filament\Resources\Warehouses\RelationManagers;

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\ReportDamagedStockAction;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
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
 * WarehouseStock rows are never created/deleted directly — they come into
 * existence the first time App\Actions\Inventory\AdjustStockAction touches
 * a (warehouse, product|variation) pair (see LocksWarehouseStock). The
 * "Add stock" header action is this relation manager's entry point into
 * that action for a product/variation that has no row here yet.
 */
class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->placeholder('—'),
                TextColumn::make('variation.sku')
                    ->label('Variation')
                    ->placeholder('—'),
                TextColumn::make('on_hand')->numeric(),
                TextColumn::make('reserved')->numeric(),
                TextColumn::make('damaged')->numeric(),
                TextColumn::make('incoming')->numeric(),
                TextColumn::make('available')
                    ->state(fn (WarehouseStock $record) => $record->available())
                    ->badge()
                    ->color(fn (WarehouseStock $record) => $record->available() > 0 ? 'success' : 'danger'),
            ])
            ->headerActions([
                Action::make('addStock')
                    ->label('Add stock')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn (RelationManager $livewire) => $livewire->getOwnerRecord()->vendor->products()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Textarea::make('reason')
                            ->required(),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        /** @var Warehouse $warehouse */
                        $warehouse = $livewire->getOwnerRecord();
                        $product = Product::findOrFail($data['product_id']);

                        app(AdjustStockAction::class)->handle($warehouse, $product, (int) $data['quantity'], $data['reason'], auth()->user());

                        Notification::make()->title('Stock added')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('adjust')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        TextInput::make('delta')
                            ->label('Adjustment (+/-)')
                            ->numeric()
                            ->required(),
                        Textarea::make('reason')
                            ->required(),
                    ])
                    ->action(function (WarehouseStock $record, array $data) {
                        [$product, $variation] = static::sellableFor($record);

                        app(AdjustStockAction::class)->handle($record->warehouse, $product ?? $variation, (int) $data['delta'], $data['reason'], auth()->user());

                        Notification::make()->title('Stock adjusted')->success()->send();
                    }),
                Action::make('reportDamage')
                    ->label('Report damage')
                    ->color('danger')
                    ->schema([
                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Textarea::make('reason')
                            ->required(),
                    ])
                    ->action(function (WarehouseStock $record, array $data) {
                        [$product, $variation] = static::sellableFor($record);

                        app(ReportDamagedStockAction::class)->handle($record->warehouse, $product ?? $variation, (int) $data['quantity'], $data['reason'], auth()->user());

                        Notification::make()->title('Damage reported')->success()->send();
                    }),
            ]);
    }

    /**
     * @return array{0: ?Product, 1: ?ProductVariation}
     */
    private static function sellableFor(WarehouseStock $record): array
    {
        return $record->product_id
            ? [$record->product, null]
            : [null, $record->variation];
    }
}
