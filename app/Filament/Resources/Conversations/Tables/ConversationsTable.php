<?php

namespace App\Filament\Resources\Conversations\Tables;

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
                TextColumn::make('vendor.business_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('With')
                    ->searchable(),
                TextColumn::make('subject')
                    ->placeholder('—')
                    ->limit(40),
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
