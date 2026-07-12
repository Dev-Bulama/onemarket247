<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Status is deliberately not an editable field here — vendor status only
 * moves through the approve/reject/suspend/terminate actions on
 * VendorsTable, which are audited and permission-gated individually (see
 * VendorPolicy). This form only edits the vendor's own business/banking
 * details, matching the Phase 2 decision that vendors are provisioned via
 * VendorApplication approval, not created directly here (create() is
 * disabled — see VendorResource/VendorPolicy).
 */
class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('business_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('registration_number')
                    ->maxLength(255),
                TextInput::make('tax_identification_number')
                    ->maxLength(255),
                TextInput::make('identity_type')
                    ->maxLength(255),
                TextInput::make('identity_number')
                    ->maxLength(255),
                TextInput::make('commission_rate')
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100),
                Toggle::make('is_verified'),
                Toggle::make('is_featured'),
                TextInput::make('bank_name')
                    ->maxLength(255),
                TextInput::make('bank_account_name')
                    ->maxLength(255),
                TextInput::make('bank_account_number')
                    ->maxLength(255),
                Select::make('country_id')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('state_id')
                    ->relationship('state', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('city_id')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('postal_code')
                    ->maxLength(255),
                Textarea::make('address')
                    ->columnSpanFull(),
            ]);
    }
}
