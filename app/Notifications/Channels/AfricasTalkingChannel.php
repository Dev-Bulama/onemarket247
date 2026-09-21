<?php

namespace App\Notifications\Channels;

use App\Models\SmsSetting;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends via Africa's Talking' REST messaging API using admin-entered
 * credentials (see App\Filament\Pages\SmsSettings) — never hardcoded.
 * Guarded so an SMS failure (misconfigured credentials, the gateway being
 * down, a missing phone number) never throws back into the queue worker
 * and never blocks the notification's other channels (mail, database).
 */
class AfricasTalkingChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $settings = SmsSetting::current();

        if (! $settings->is_active || ! $settings->username || ! $settings->api_key) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (! $message->to || ! $message->body) {
            return;
        }

        try {
            static::sendRaw($settings, $message);
        } catch (Throwable $exception) {
            report($exception);
            Log::warning("Africa's Talking SMS send failed", ['to' => $message->to, 'exception' => $exception->getMessage()]);
        }
    }

    /**
     * The actual Africa's Talking API call, split out so a caller that
     * wants real success/failure feedback (App\Filament\Pages\SmsSettings'
     * "Send test SMS" action) can call it directly instead of through
     * send() above, which deliberately swallows every failure so an SMS
     * problem never blocks a notification's other channels.
     */
    public static function sendRaw(SmsSetting $settings, SmsMessage $message): void
    {
        $baseUrl = $settings->sandbox
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';

        Http::asForm()
            ->withHeaders([
                'apiKey' => $settings->api_key,
                'Accept' => 'application/json',
            ])
            ->post($baseUrl, array_filter([
                'username' => $settings->username,
                'to' => $message->to,
                'message' => $message->body,
                'from' => $settings->sender_id,
            ]))
            ->throw();
    }
}
