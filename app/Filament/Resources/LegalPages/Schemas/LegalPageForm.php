<?php

namespace App\Filament\Resources\LegalPages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LegalPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page')
                ->columns(2)
                ->schema([
                    TextInput::make('key')->disabled()->dehydrated(false),
                    TextInput::make('title')->required()->maxLength(255),
                ]),
            Section::make('Content')
                ->description('Basic HTML tags (e.g. <h3>, <p>, <strong>, <ul>/<li>) are supported and rendered as formatted text on the storefront and in the app.')
                ->columns(1)
                ->schema([
                    Textarea::make('body')->required()->rows(20),
                ]),
        ]);
    }
}
