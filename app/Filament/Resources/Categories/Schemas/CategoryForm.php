<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Support\CategoryIcons;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('tmp-category-media')
                    ->visibility('public')
                    ->columnSpanFull(),
                Select::make('parent_id')
                    ->label('Parent category')
                    ->relationship('parent', 'name', fn ($query, $record) => $record
                        ? $query->whereKeyNot($record->id)
                        : $query)
                    ->searchable(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('icon')
                    ->label('Icon')
                    ->options(CategoryIcons::options())
                    ->searchable()
                    ->helperText('Shown in the category menu and sidebar.'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->default(true),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('seo_title')
                    ->maxLength(255),
                TextInput::make('seo_description')
                    ->maxLength(255),
            ]);
    }
}
