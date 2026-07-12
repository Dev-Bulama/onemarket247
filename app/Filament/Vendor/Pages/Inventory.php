<?php

namespace App\Filament\Vendor\Pages;

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\ReportDamagedStockAction;
use App\Actions\Inventory\RequestStockTransferAction;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * "/vendor/inventory" per docs/architecture/07-vendor-dashboard.md: stock
 * levels across the vendor's own warehouses, with adjustments and transfer
 * requests. WarehouseStock rows are lazily created the first time
 * AdjustStockAction touches a (warehouse, product|variation) pair, so
 * "Add stock" is the entry point for a product that has no row here yet.
 */
class Inventory extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.vendor.pages.inventory';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = Auth::guard('vendor')->user();

        return $user->vendor !== null || $user->can('store.inventory.manage');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(WarehouseStock::query()->whereHas('warehouse', fn (Builder $query) => $query->where('vendor_id', $this->vendorId())))
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
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
                        Select::make('warehouse_id')
                            ->label('Warehouse')
                            ->options(fn () => Warehouse::query()->where('vendor_id', $this->vendorId())->pluck('name', 'id'))
                            ->required(),
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn () => Product::query()->where('vendor_id', $this->vendorId())->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Textarea::make('reason')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $warehouse = Warehouse::findOrFail($data['warehouse_id']);
                        $product = Product::findOrFail($data['product_id']);

                        app(AdjustStockAction::class)->handle($warehouse, $product, (int) $data['quantity'], $data['reason'], Auth::guard('vendor')->user());

                        Notification::make()->title('Stock added')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('adjust')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
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

                        app(AdjustStockAction::class)->handle($record->warehouse, $product ?? $variation, (int) $data['delta'], $data['reason'], Auth::guard('vendor')->user());

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

                        app(ReportDamagedStockAction::class)->handle($record->warehouse, $product ?? $variation, (int) $data['quantity'], $data['reason'], Auth::guard('vendor')->user());

                        Notification::make()->title('Damage reported')->success()->send();
                    }),
                Action::make('requestTransfer')
                    ->label('Request transfer')
                    ->color('info')
                    ->schema(fn (WarehouseStock $record) => [
                        Select::make('to_warehouse_id')
                            ->label('Destination warehouse')
                            ->options(Warehouse::query()
                                ->where('vendor_id', $this->vendorId())
                                ->whereKeyNot($record->warehouse_id)
                                ->pluck('name', 'id'))
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Textarea::make('notes'),
                    ])
                    ->action(function (WarehouseStock $record, array $data) {
                        [$product, $variation] = static::sellableFor($record);
                        $toWarehouse = Warehouse::findOrFail($data['to_warehouse_id']);

                        app(RequestStockTransferAction::class)->handle(
                            $record->warehouse,
                            $toWarehouse,
                            [['sellable' => $product ?? $variation, 'quantity' => (int) $data['quantity']]],
                            Auth::guard('vendor')->user(),
                            $data['notes'] ?? null,
                        );

                        Notification::make()->title('Transfer requested')->success()->send();
                    }),
            ]);
    }

    private function vendorId(): ?int
    {
        return Auth::guard('vendor')->user()->actingVendorId();
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
