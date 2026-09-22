<?php

namespace App\Filament\Vendor\Resources\Products\RelationManagers;

use App\Actions\Inventory\AdjustStockAction;
use App\Models\ProductVariation;
use App\Support\Filament\MinorUnitsInput;
use App\Support\PriceDisplay;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    /**
     * Staged image path captured in mutateFormDataUsing() (since the
     * column doesn't exist on ProductVariation itself) and consumed in
     * the create/edit action's after() hook, once the record exists —
     * same staged-upload shape as HandlesProductMedia, just inline here
     * since a relation manager's actions don't have separate page
     * lifecycle methods to put it in.
     */
    private ?string $stagedVariationImage = null;

    private function attachStagedVariationImage(ProductVariation $record): void
    {
        $path = $this->stagedVariationImage;
        $this->stagedVariationImage = null;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return;
        }

        $record->clearMediaCollection('images');
        $record->addMediaFromDisk($path, 'public')->toMediaCollection('images');
        Storage::disk('public')->delete($path);
    }

    /**
     * Without this, a new variation's stock_quantity is just whatever the
     * form said, with no real WarehouseStock backing it — every "add to
     * cart" attempt on it fails at checkout's selectWarehouseStock(),
     * which requires a real warehouse_stocks row (see
     * RecalculatesSellableStock's docblock) — same bug class already
     * fixed for base products in CreateProduct::handleRecordCreation() and
     * the vendor API's ProductController::store().
     */
    private function seedInitialVariationStock(ProductVariation $record): void
    {
        if (! $record->manage_stock) {
            return;
        }

        $warehouse = $this->getOwnerRecord()->vendor?->defaultWarehouse();

        if (! $warehouse) {
            return;
        }

        app(AdjustStockAction::class)->handle(
            $warehouse,
            $record,
            $record->stock_quantity,
            'Initial stock at variation creation',
            Auth::guard('vendor')->user(),
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix(PriceDisplay::baseCurrencyCode())
                    ->helperText('The selling price for this variation, e.g. 29.99.')
                    ->afterStateHydrated(MinorUnitsInput::hydrate())
                    ->dehydrateStateUsing(MinorUnitsInput::dehydrate()),
                TextInput::make('compare_at_price')
                    ->numeric()
                    ->prefix(PriceDisplay::baseCurrencyCode())
                    ->helperText('Optional "was" price shown struck through, e.g. 39.99.')
                    ->afterStateHydrated(MinorUnitsInput::hydrate())
                    ->dehydrateStateUsing(MinorUnitsInput::dehydrate()),
                TextInput::make('stock_quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                CheckboxList::make('attributeValues')
                    ->label('Attribute values')
                    ->relationship('attributeValues', 'value', fn ($query) => $query->whereHas('attribute', fn ($q) => $q->where('is_variation', true)))
                    ->columns(2),
                Toggle::make('is_active')
                    ->default(true),
                FileUpload::make('image')
                    ->label('Photo (optional)')
                    ->image()
                    ->disk('public')
                    ->directory('tmp-product-media')
                    ->visibility('public')
                    ->helperText('Uploading a new photo replaces the current one.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('sku')
                    ->searchable(),
                TextColumn::make('attributeValues.value')
                    ->badge()
                    ->label('Attributes'),
                TextColumn::make('price')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                TextColumn::make('stock_quantity')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->boolean(),
                ImageColumn::make('image')
                    ->label('')
                    ->getStateUsing(fn (ProductVariation $record) => $record->getFirstMediaUrl('images') ?: null),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $this->stagedVariationImage = $data['image'] ?? null;
                        unset($data['image']);

                        return $data;
                    })
                    ->after(function (ProductVariation $record) {
                        // manage_stock isn't a form field — Eloquent never
                        // populated it on this in-memory instance, so it
                        // reads as null here even though the DB column
                        // default (true) really did apply to the row.
                        $record->refresh();
                        $this->attachStagedVariationImage($record);
                        $this->seedInitialVariationStock($record);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $this->stagedVariationImage = $data['image'] ?? null;
                        unset($data['image']);

                        return $data;
                    })
                    ->after(function (ProductVariation $record) {
                        $this->attachStagedVariationImage($record);
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
