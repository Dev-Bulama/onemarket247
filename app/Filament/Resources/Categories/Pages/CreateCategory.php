<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\HandlesStagedSingleImage;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCategory extends CreateRecord
{
    use HandlesStagedSingleImage;

    protected static string $resource = CategoryResource::class;

    private ?string $stagedImage = null;

    protected function handleRecordCreation(array $data): Model
    {
        $this->stagedImage = $data['image'] ?? null;
        unset($data['image']);

        return Category::create($data);
    }

    protected function afterCreate(): void
    {
        $this->attachStagedImage($this->record, $this->stagedImage, 'image');
    }
}
