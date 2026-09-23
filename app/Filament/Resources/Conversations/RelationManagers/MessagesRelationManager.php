<?php

namespace App\Filament\Resources\Conversations\RelationManagers;

use App\Actions\Chat\SendMessageAction;
use App\Models\Conversation;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')
                ->label('Message')
                ->required()
                ->maxLength(2000),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('sender.name')
                    ->label('From'),
                TextColumn::make('body'),
                TextColumn::make('read_at')
                    ->label('Read')
                    ->dateTime()
                    ->placeholder('Not yet'),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Send message as admin')
                    ->using(function (array $data) {
                        /** @var Conversation $conversation */
                        $conversation = $this->getOwnerRecord();

                        return app(SendMessageAction::class)->handle($conversation, auth()->user(), $data['body']);
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Remove (moderation)'),
            ]);
    }
}
