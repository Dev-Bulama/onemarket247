<?php

namespace App\Notifications\Messages;

/**
 * The push-notification analogue of Illuminate\Notifications\Messages\MailMessage
 * — a notification's toOneSignal($notifiable) method builds and returns
 * one of these, and App\Notifications\Channels\OneSignalChannel reads it.
 */
class OneSignalMessage
{
    public string $title = '';

    public string $body = '';

    /** @var array<string, mixed> */
    public array $data = [];

    public static function create(string $title = '', string $body = ''): self
    {
        return (new self)->title($title)->body($body);
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function data(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
