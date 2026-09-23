<?php

namespace App\Actions\Chat;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\NewChatMessageNotification;

class SendMessageAction
{
    public function handle(Conversation $conversation, User $sender, string $body): ChatMessage
    {
        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        $recipient = $conversation->isVendorSide($sender)
            ? $conversation->user
            : $conversation->vendor?->user;

        $recipient?->notify(new NewChatMessageNotification($message->fresh('sender')));

        return $message;
    }
}
