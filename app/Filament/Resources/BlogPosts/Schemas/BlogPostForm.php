<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Enums\BlogPostStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('cover')
                    ->image()
                    ->disk('public')
                    ->directory('tmp-blog-media')
                    ->visibility('public')
                    ->columnSpanFull(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('excerpt')
                    ->maxLength(500)
                    ->helperText('Shown on the blog listing page. Auto-generated from the body if left blank.')
                    ->columnSpanFull(),
                Textarea::make('body')
                    ->required()
                    ->rows(12)
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(BlogPostStatus::class)
                    ->default(BlogPostStatus::Draft->value)
                    ->required(),
                DateTimePicker::make('published_at'),
                TextInput::make('seo_title')
                    ->maxLength(255),
                TextInput::make('seo_description')
                    ->maxLength(255),
            ]);
    }
}
