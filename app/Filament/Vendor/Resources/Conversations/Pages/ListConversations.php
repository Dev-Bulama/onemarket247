<?php

namespace App\Filament\Vendor\Resources\Conversations\Pages;

use App\Filament\Vendor\Resources\Conversations\ConversationResource;
use Filament\Resources\Pages\ListRecords;

class ListConversations extends ListRecords
{
    protected static string $resource = ConversationResource::class;
}
