<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription('This suspends the vendor and can be undone with Restore. Their login and email stay reserved — to fully remove the account and free up their email for re-registration, use Force Delete instead.'),
            ForceDeleteAction::make()
                ->modalDescription('This permanently deletes the vendor\'s login account too, freeing their email for a new registration. This cannot be undone.'),
            RestoreAction::make(),
        ];
    }
}
