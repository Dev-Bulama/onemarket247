<?php

namespace App\Filament\Vendor\Resources\Conversations\Tables;

use App\Models\Conversation;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('subject')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('product.name')
                    ->label('About product')
                    ->placeholder('—'),
                TextColumn::make('latestMessage.body')
                    ->label('Last message')
                    ->limit(50)
                    ->placeholder('—'),
                IconColumn::make('is_closed')
                    ->label('Closed')
                    ->boolean()
                    ->state(fn (Conversation $record) => $record->isClosed()),
                TextColumn::make('last_message_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                TernaryFilter::make('closed_at')
                    ->label('Closed')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
