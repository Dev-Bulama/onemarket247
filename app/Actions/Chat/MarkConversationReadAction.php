<?php

namespace App\Actions\Chat;

use App\Models\Conversation;
use App\Models\User;

class MarkConversationReadAction
{
    public function handle(Conversation $conversation, User $reader): void
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $reader->id)
            ->update(['read_at' => now()]);
    }
}
