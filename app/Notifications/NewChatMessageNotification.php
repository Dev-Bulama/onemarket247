<?php

namespace App\Notifications;

use App\Models\ChatMessage;
use App\Notifications\Channels\OneSignalChannel;
use App\Notifications\Messages\OneSignalMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Sent to the other party in a Conversation whenever a new ChatMessage
 * lands — database channel so it appears in the recipient's existing
 * account notifications (GET /api/v1/notifications, same list the bell
 * icon already reads), plus a native push via OneSignal if they have a
 * registered device. No mail: chat is meant to feel immediate, and an
 * email per message would be spam.
 */
class NewChatMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ChatMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['database', OneSignalChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'conversation_id' => $this->message->conversation_id,
            'sender_name' => $this->message->sender->name,
            'preview' => Str::limit($this->message->body, 80),
        ];
    }

    public function toOneSignal(object $notifiable): OneSignalMessage
    {
        return OneSignalMessage::create('New message from '.$this->message->sender->name, Str::limit($this->message->body, 120))
            ->data(['type' => 'chat_message', 'conversation_id' => $this->message->conversation_id]);
    }
}
