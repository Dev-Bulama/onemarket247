<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Concerns\HandlesStagedSingleImage;
use App\Filament\Resources\Brands\BrandResource;
use App\Models\Brand;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBrand extends CreateRecord
{
    use HandlesStagedSingleImage;

    protected static string $resource = BrandResource::class;

    private ?string $stagedImage = null;

    protected function handleRecordCreation(array $data): Model
    {
        $this->stagedImage = $data['logo'] ?? null;
        unset($data['logo']);

        return Brand::create($data);
    }

    protected function afterCreate(): void
    {
        $this->attachStagedImage($this->record, $this->stagedImage, 'logo');
    }
}
