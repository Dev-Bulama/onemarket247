<?php

namespace App\Filament\Resources\Administrators\Pages;

use App\Enums\UserStatus;
use App\Filament\Resources\Administrators\AdministratorResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdministrator extends CreateRecord
{
    protected static string $resource = AdministratorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] ??= UserStatus::Active->value;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::create($data);

        // email_verified_at is intentionally excluded from User's fillable
        // list (see App\Actions\Auth\RegisterOrLoginSocialUserAction for
        // why); an administrator created by another administrator is
        // considered pre-verified.
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}
