<?php

namespace App\Filament\Resources\Conversations;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\Conversations\Pages\ListConversations;
use App\Filament\Resources\Conversations\Pages\ViewConversation;
use App\Filament\Resources\Conversations\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\Conversations\Tables\ConversationsTable;
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
 * Admin moderation of every chat thread across the marketplace — read
 * access plus the ability to reply, close/reopen, and delete individual
 * messages (see MessagesRelationManager). Conversations are always
 * started by a customer or an admin (see ConversationPolicy::create()) and
 * replied to by either side via the mobile API — this resource is purely
 * the admin-side moderation surface, not how vendors manage their inbox.
 */
class ConversationResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'conversations.moderate';

    protected static ?string $model = Conversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Engagement';

    protected static ?string $navigationLabel = 'Conversations';

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
                    TextEntry::make('vendor.business_name')->label('Vendor'),
                    TextEntry::make('user.name')->label('With'),
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
