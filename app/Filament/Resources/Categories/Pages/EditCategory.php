<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\HandlesStagedSingleImage;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    use HandlesStagedSingleImage;

    protected static string $resource = CategoryResource::class;

    private ?string $stagedImage = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->stagedImage = $data['image'] ?? null;
        unset($data['image']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->attachStagedImage($this->record, $this->stagedImage, 'image');
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
