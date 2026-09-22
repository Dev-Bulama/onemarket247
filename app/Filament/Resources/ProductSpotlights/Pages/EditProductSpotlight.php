<?php

namespace App\Filament\Resources\ProductSpotlights\Pages;

use App\Filament\Resources\ProductSpotlights\ProductSpotlightResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductSpotlight extends EditRecord
{
    protected static string $resource = ProductSpotlightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
