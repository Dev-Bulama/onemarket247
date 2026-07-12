<?php

namespace App\Filament\Vendor\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Vendor\Resources\Products\Concerns\HandlesProductMedia;
use App\Filament\Vendor\Resources\Products\ProductResource;
use App\Models\Product;
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

        $data['vendor_id'] = Auth::guard('vendor')->user()->actingVendorId();
        $data['status'] = ProductStatus::Draft->value;

        /** @var Product $product */
        $product = Product::create($data);

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
