<?php

namespace App\Notifications\Channels;

use App\Models\PushSetting;
use App\Models\User;
use App\Notifications\Messages\OneSignalMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends via OneSignal's REST API using an admin-entered App ID + REST API
 * key (see App\Filament\Pages\PushSettings) — never hardcoded credentials.
 * Guarded so a push failure (misconfigured credentials, OneSignal being
 * down, a user with no registered device) never throws back into the
 * queue worker and never blocks the notification's other channels (mail,
 * database) from delivering.
 */
class OneSignalChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toOneSignal')) {
            return;
        }

        $settings = PushSetting::current();

        if (! $settings->is_active || ! $settings->app_id || ! $settings->rest_api_key) {
            return;
        }

        if (! $notifiable instanceof User) {
            return;
        }

        $playerIds = $notifiable->deviceTokens()->pluck('token')->all();

        if ($playerIds === []) {
            return;
        }

        $message = $notification->toOneSignal($notifiable);

        try {
            static::sendRaw($settings->app_id, $settings->rest_api_key, $playerIds, $message);
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('OneSignal push send failed', ['user_id' => $notifiable->id, 'exception' => $exception->getMessage()]);
        }
    }

    /**
     * The actual OneSignal API call, split out so a caller that wants real
     * success/failure feedback (App\Filament\Pages\PushSettings' "Send
     * test push" action) can call it directly instead of through send()
     * above, which deliberately swallows every failure so a push problem
     * never blocks a notification's other channels (mail, database).
     *
     * @param  array<int, string>  $playerIds
     */
    public static function sendRaw(string $appId, string $restApiKey, array $playerIds, OneSignalMessage $message): void
    {
        Http::withHeaders(['Authorization' => 'Basic '.$restApiKey])
            ->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => $appId,
                'include_player_ids' => $playerIds,
                'headings' => ['en' => $message->title],
                'contents' => ['en' => $message->body],
                'data' => $message->data,
            ])
            ->throw();
    }
}
