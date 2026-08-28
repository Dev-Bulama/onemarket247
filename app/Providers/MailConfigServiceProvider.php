<?php

namespace App\Providers;

use App\Models\MailSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Lets an admin configure real SMTP credentials from the "Mail Settings"
 * page (App\Filament\Pages\MailSettings) instead of needing .env/server
 * access — see MailSetting's docblock. Runs once per request/command,
 * before anything could send mail, and overrides the exact same config
 * keys config/mail.php would otherwise fill from MAIL_* env vars.
 *
 * Guarded with a table-exists check + try/catch: this provider boots on
 * every request including `artisan migrate` itself, before the
 * mail_settings table exists on a fresh install.
 */
class MailConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('mail_settings')) {
                return;
            }

            $settings = MailSetting::query()->first();

            if (! $settings || ! $settings->is_active) {
                return;
            }

            config([
                'mail.default' => $settings->mailer,
                'mail.mailers.'.$settings->mailer.'.transport' => $settings->mailer,
                'mail.mailers.'.$settings->mailer.'.host' => $settings->host,
                'mail.mailers.'.$settings->mailer.'.port' => $settings->port,
                'mail.mailers.'.$settings->mailer.'.username' => $settings->username,
                'mail.mailers.'.$settings->mailer.'.password' => $settings->password,
                'mail.mailers.'.$settings->mailer.'.encryption' => $settings->encryption,
                'mail.from.address' => $settings->from_address ?: config('mail.from.address'),
                'mail.from.name' => $settings->from_name ?: config('mail.from.name'),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
