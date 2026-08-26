<?php

namespace App\Filament\Resources\HeroSlides\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HeroSlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image_path')
                    ->label('Photo')
                    ->image()
                    ->disk('public')
                    ->directory('hero-slides')
                    ->visibility('public')
                    ->required()
                    ->maxSize(8192)
                    ->helperText('Recommended: a landscape photo around 1600×900px. Every slide is cropped to the same frame, so photos of different sizes still line up.')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Show on homepage')
                    ->default(true),
            ]);
    }
}
