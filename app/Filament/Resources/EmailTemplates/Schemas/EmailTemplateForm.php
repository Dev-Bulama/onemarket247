<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template')
                ->columns(2)
                ->schema([
                    TextInput::make('key')->disabled()->dehydrated(false),
                    TextInput::make('name')->disabled()->dehydrated(false),
                    Toggle::make('is_active')
                        ->columnSpanFull()
                        ->helperText('While off, the app keeps using its built-in default copy for this email — nothing changes for recipients until you turn this on.'),
                ]),
            Section::make('Content')
                ->description(fn ($record) => filled($record?->placeholders)
                    ? 'Available placeholders: '.collect($record->placeholders)->map(fn ($p) => '{{'.$p.'}}')->implode(', ')
                    : null)
                ->columns(1)
                ->schema([
                    TextInput::make('subject')->required()->maxLength(255),
                    Textarea::make('body')->required()->rows(8),
                ]),
        ]);
    }
}
