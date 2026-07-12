<?php

namespace App\Filament\Resources\Attributes\Schemas;

use App\Enums\AttributeInputType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('attribute_group_id')
                    ->label('Group')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('input_type')
                    ->options(AttributeInputType::class)
                    ->default('select')
                    ->required(),
                Toggle::make('is_filterable')
                    ->default(true),
                Toggle::make('is_variation')
                    ->helperText('Whether this attribute can be used to define product variations.')
                    ->default(false),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
