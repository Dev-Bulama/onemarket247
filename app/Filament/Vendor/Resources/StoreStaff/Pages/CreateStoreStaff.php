<?php

namespace App\Filament\Vendor\Resources\StoreStaff\Pages;

use App\Actions\Vendor\InviteStoreStaffAction;
use App\Filament\Vendor\Resources\StoreStaff\StoreStaffResource;
use App\Models\Store;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class CreateStoreStaff extends CreateRecord
{
    protected static string $resource = StoreStaffResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                CheckboxList::make('permissions')
                    ->label('Store permissions')
                    ->options(fn () => Permission::where('guard_name', 'vendor')->pluck('name', 'name')),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $vendorId = Auth::guard('vendor')->user()->actingVendorId();
        $store = Store::withoutGlobalScopes()->where('vendor_id', $vendorId)->firstOrFail();

        return app(InviteStoreStaffAction::class)->handle(
            $store,
            $data['name'],
            $data['email'],
            $data['permissions'] ?? [],
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
