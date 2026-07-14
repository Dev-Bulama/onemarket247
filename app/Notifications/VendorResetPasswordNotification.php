<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

/**
 * Laravel's stock ResetPassword notification hardcodes route('password.reset'),
 * which belongs to the customer reset flow. Vendor owners/staff reset through
 * a distinct guard and route (vendor.password.reset, validated against the
 * "vendors" broker) — without this override their reset link 404s/points at
 * a broker that can never find them, since they aren't user_type customer.
 */
class VendorResetPasswordNotification extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return url(route('vendor.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
