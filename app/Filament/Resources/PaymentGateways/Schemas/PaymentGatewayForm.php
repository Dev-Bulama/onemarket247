<?php

namespace App\Filament\Resources\PaymentGateways\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentGatewayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Gateway')
                ->columns(2)
                ->schema([
                    TextInput::make('code')->disabled()->dehydrated(false),
                    TextInput::make('name')->disabled()->dehydrated(false),
                    Toggle::make('is_active')
                        ->helperText('Only an active gateway is offered at checkout.'),
                ]),
            // Secrets are encrypted casts and never round-tripped to the
            // browser — each field always loads blank; submitting a blank
            // value leaves the stored secret untouched (see
            // docs/architecture/10-security-architecture.md "Payment Security").
            Section::make('Credentials')
                ->description('Leave a field blank to keep its current value.')
                ->columns(1)
                ->schema([
                    TextInput::make('public_key')
                        ->password()
                        ->revealable()
                        ->afterStateHydrated(fn ($component) => $component->state(null))
                        ->dehydrated(fn ($state) => filled($state)),
                    TextInput::make('secret_key')
                        ->password()
                        ->revealable()
                        ->afterStateHydrated(fn ($component) => $component->state(null))
                        ->dehydrated(fn ($state) => filled($state)),
                    TextInput::make('webhook_secret')
                        ->password()
                        ->revealable()
                        ->afterStateHydrated(fn ($component) => $component->state(null))
                        ->dehydrated(fn ($state) => filled($state)),
                ]),
        ]);
    }
}
