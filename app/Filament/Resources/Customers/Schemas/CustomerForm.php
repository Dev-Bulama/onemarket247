<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\UserStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
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
                Select::make('status')
                    ->options(UserStatus::class)
                    ->required()
                    ->helperText('Suspending or banning a customer blocks them from logging in.'),
            ]);
    }
}
