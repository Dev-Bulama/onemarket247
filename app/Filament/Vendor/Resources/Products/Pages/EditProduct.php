<?php

namespace App\Filament\Vendor\Resources\Products\Pages;

use App\Filament\Vendor\Resources\Products\Concerns\HandlesProductMedia;
use App\Filament\Vendor\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

/**
 * The images/digital_files fields are staged-upload-only virtual fields
 * (see ProductForm) — they are never hydrated from the record on edit, so
 * this page can only append newly uploaded files, not manage/remove
 * previously attached media. That is an accepted scope limit for this
 * phase (see HandlesProductMedia).
 */
class EditProduct extends EditRecord
{
    use HandlesProductMedia;

    protected static string $resource = ProductResource::class;

    private array $stagedImages = [];

    private array $stagedDigitalFiles = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->stagedImages = $data['images'] ?? [];
        $this->stagedDigitalFiles = $data['digital_files'] ?? [];

        unset($data['images'], $data['digital_files']);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Product $product */
        $product = $this->record;

        $this->attachStagedImages($product, $this->stagedImages);
        $this->attachStagedDigitalFiles($product, $this->stagedDigitalFiles);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
