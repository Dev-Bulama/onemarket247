<?php

namespace App\Filament\Resources\ProductQuestions;

use App\Filament\Resources\ProductQuestions\Pages\ListProductQuestions;
use App\Filament\Resources\ProductQuestions\Pages\ViewProductQuestion;
use App\Filament\Resources\ProductQuestions\RelationManagers\AnswersRelationManager;
use App\Filament\Resources\ProductQuestions\Tables\ProductQuestionsTable;
use App\Models\ProductQuestion;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Read-only + relation-driven, like ProductReviewResource: questions are
 * asked by customers, only answered here (via the Answers relation
 * manager on the view page).
 */
class ProductQuestionResource extends Resource
{
    protected static ?string $model = ProductQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Engagement';

    protected static ?string $navigationLabel = 'Questions';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return ProductQuestionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Question')
                ->columns(2)
                ->schema([
                    TextEntry::make('product.name')->label('Product'),
                    TextEntry::make('customer.name')->label('Asked by'),
                    TextEntry::make('question')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            AnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductQuestions::route('/'),
            'view' => ViewProductQuestion::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
