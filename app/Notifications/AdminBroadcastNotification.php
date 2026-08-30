<?php

namespace App\Notifications;

use App\Notifications\Channels\OneSignalChannel;
use App\Notifications\Messages\OneSignalMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A message an admin sends directly to one or many users (see
 * App\Actions\Admin\SendAdminMessageAction) — appears in the recipient's
 * account notifications (database channel, so mobile/web can list it via
 * GET /api/v1/notifications), as an email using the same branding every
 * other notification gets, and — for a recipient with a registered mobile
 * device and an admin-configured OneSignal account (see
 * App\Filament\Pages\PushSettings) — as a native push notification.
 * Queued: a broadcast can target thousands of users at once, and each
 * send must never block the admin's request or one failed mailbox/push
 * from affecting the rest of the run.
 */
class AdminBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $subject,
        private readonly string $body,
        private readonly ?string $senderName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', OneSignalChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->greeting('Hello '.$notifiable->name.',');

        foreach (explode("\n", $this->body) as $paragraph) {
            if (trim($paragraph) !== '') {
                $mail->line($paragraph);
            }
        }

        if ($this->senderName) {
            $mail->salutation('— '.$this->senderName.', '.config('app.name'));
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'body' => $this->body,
            'sender_name' => $this->senderName,
        ];
    }

    public function toOneSignal(object $notifiable): OneSignalMessage
    {
        return OneSignalMessage::create($this->subject, $this->body);
    }
}
