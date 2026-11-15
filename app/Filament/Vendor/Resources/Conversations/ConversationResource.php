<?php

namespace App\Filament\Vendor\Resources\Conversations;

use App\Filament\Vendor\Resources\Conversations\Pages\ListConversations;
use App\Filament\Vendor\Resources\Conversations\Pages\ViewConversation;
use App\Filament\Vendor\Resources\Conversations\RelationManagers\MessagesRelationManager;
use App\Filament\Vendor\Resources\Conversations\Tables\ConversationsTable;
use App\Models\Conversation;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The vendor's own chat inbox — Conversation already carries
 * BelongsToVendorScope, so this resource only ever sees the authenticated
 * vendor guard user's own store's conversations with no extra scoping
 * needed (same pattern as VendorOrderResource). Authorization comes from
 * ConversationPolicy, auto-resolved by Filament from the model name — see
 * its hasVendorAccess() for the owner/active-staff + store.conversations.manage
 * check.
 */
class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Chats';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return ConversationsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Conversation')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.name')->label('Customer'),
                    TextEntry::make('subject')->placeholder('—'),
                    TextEntry::make('product.name')->label('About product')->placeholder('—'),
                    IconEntry::make('is_closed')->label('Closed')->boolean()->state(fn (Conversation $record) => $record->isClosed()),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
            'view' => ViewConversation::route('/{record}'),
        ];
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
