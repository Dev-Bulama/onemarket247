<?php

namespace App\Filament\Vendor\Resources\Products\Pages;

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\ProductStatus;
use App\Filament\Vendor\Resources\Products\Concerns\HandlesProductMedia;
use App\Filament\Vendor\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\Vendor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateProduct extends CreateRecord
{
    use HandlesProductMedia;

    protected static string $resource = ProductResource::class;

    private array $stagedImages = [];

    private array $stagedDigitalFiles = [];

    protected function handleRecordCreation(array $data): Model
    {
        $this->stagedImages = $data['images'] ?? [];
        $this->stagedDigitalFiles = $data['digital_files'] ?? [];

        unset($data['images'], $data['digital_files']);

        $vendorId = Auth::guard('vendor')->user()->actingVendorId();
        $data['vendor_id'] = $vendorId;
        $data['status'] = ProductStatus::Draft->value;

        // The stock_quantity/stock_status fields above are just the
        // vendor's requested *initial* stock — warehouse_stocks is the
        // real source of truth (see RecalculatesSellableStock's docblock).
        // Without seeding a real WarehouseStock row here, a product
        // created with stock_quantity left blank ends up manage_stock=true,
        // stock_status=in_stock, stock_quantity=null — looking purchasable
        // while silently failing every "add to cart" attempt.
        $initialQuantity = $data['stock_quantity'] ?? 0;
        $manageStock = $data['manage_stock'] ?? true;

        /** @var Product $product */
        $product = Product::create($data);

        if ($manageStock) {
            $warehouse = Vendor::find($vendorId)?->defaultWarehouse();

            if ($warehouse) {
                app(AdjustStockAction::class)->handle($warehouse, $product, $initialQuantity, 'Initial stock at product creation');
            }
        }

        return $product;
    }

    protected function afterCreate(): void
    {
        /** @var Product $product */
        $product = $this->record;

        $this->attachStagedImages($product, $this->stagedImages);
        $this->attachStagedDigitalFiles($product, $this->stagedDigitalFiles);
    }
}
