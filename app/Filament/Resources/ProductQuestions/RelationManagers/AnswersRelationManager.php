<?php

namespace App\Filament\Resources\ProductQuestions\RelationManagers;

use App\Models\ProductQuestion;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('answer')
                ->required()
                ->maxLength(2000),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('answer')
            ->columns([
                TextColumn::make('answeredBy.name')
                    ->label('Answered by'),
                TextColumn::make('answer'),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data) {
                        $data['answered_by'] = auth()->id();

                        return $data;
                    })
                    ->after(function () {
                        /** @var ProductQuestion $question */
                        $question = $this->getOwnerRecord();
                        $question->update(['is_answered' => true]);
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
