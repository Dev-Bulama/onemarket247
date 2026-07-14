<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Concerns\HandlesStagedSingleImage;
use App\Filament\Resources\Brands\BrandResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    use HandlesStagedSingleImage;

    protected static string $resource = BrandResource::class;

    private ?string $stagedImage = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->stagedImage = $data['logo'] ?? null;
        unset($data['logo']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->attachStagedImage($this->record, $this->stagedImage, 'logo');
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
