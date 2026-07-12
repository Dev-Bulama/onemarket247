<?php

namespace App\Filament\Vendor\Resources\StoreStaff\Pages;

use App\Filament\Vendor\Resources\StoreStaff\StoreStaffResource;
use App\Models\StoreStaff;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Permission;

class EditStoreStaff extends EditRecord
{
    protected static string $resource = StoreStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var StoreStaff $record */
        $record = $this->getRecord();
        $data['permissions'] = $record->user->getPermissionNames()->all();

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var StoreStaff $record */
        $record = $this->getRecord();
        $permissions = $this->data['permissions'] ?? [];

        $record->user->syncPermissions(
            Permission::where('guard_name', 'vendor')->whereIn('name', $permissions)->get()
        );
    }
}
