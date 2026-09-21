<?php

namespace App\Notifications\Messages;

/**
 * The SMS analogue of Illuminate\Notifications\Messages\MailMessage — a
 * notification's toSms($notifiable) method builds and returns one of
 * these, and App\Notifications\Channels\AfricasTalkingChannel reads it.
 * Unlike toMail/toOneSignal, the destination phone number lives on the
 * message itself (`to`) rather than being derived from the notifiable,
 * since some SMS notifications go to a plain phone number with no User
 * account yet (e.g. a vendor application still under review).
 */
class SmsMessage
{
    public string $to = '';

    public string $body = '';

    public static function create(string $to = '', string $body = ''): self
    {
        return (new self)->to($to)->body($body);
    }

    public function to(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }
}
