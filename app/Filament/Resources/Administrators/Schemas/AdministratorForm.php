<?php

namespace App\Filament\Resources\Administrators\Schemas;

use App\Enums\UserStatus;
use App\Enums\UserType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class AdministratorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('user_type')
                    ->label('Role level')
                    ->options([
                        UserType::Admin->value => UserType::Admin->getLabel(),
                        UserType::Staff->value => UserType::Staff->getLabel(),
                    ])
                    ->default(UserType::Staff->value)
                    ->required(),
                Select::make('status')
                    ->options(UserStatus::class)
                    ->default(UserStatus::Active)
                    ->required(),
                Select::make('roles')
                    ->relationship('roles', 'name', fn ($query) => $query->where('guard_name', 'admin'))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->maxLength(255)
                    ->helperText('Leave blank to keep the current password.'),
            ]);
    }
}
