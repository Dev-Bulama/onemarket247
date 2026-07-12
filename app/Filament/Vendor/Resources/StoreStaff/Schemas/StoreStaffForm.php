<?php

namespace App\Filament\Vendor\Resources\StoreStaff\Schemas;

use App\Enums\StoreStaffStatus;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;

/**
 * Used only by the Edit page — Create has its own invite-shaped form (see
 * CreateStoreStaff::form()) since inviting a new staff member needs a
 * name/email that don't live on the StoreStaff model itself. "permissions"
 * is not a StoreStaff relationship (permissions live on the linked User);
 * EditStoreStaff loads/saves it manually via mutateFormDataBeforeFill /
 * afterSave.
 */
class StoreStaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options([
                        StoreStaffStatus::Active->value => StoreStaffStatus::Active->getLabel(),
                        StoreStaffStatus::Suspended->value => StoreStaffStatus::Suspended->getLabel(),
                    ])
                    ->required(),
                CheckboxList::make('permissions')
                    ->label('Store permissions')
                    ->options(fn () => Permission::where('guard_name', 'vendor')->pluck('name', 'name')),
            ]);
    }
}
