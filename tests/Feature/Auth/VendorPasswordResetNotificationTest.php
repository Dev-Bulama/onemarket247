<?php

use App\Models\Vendor;
use App\Notifications\VendorResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('an approved vendor password reset email links to the vendor reset route, not the customer one', function () {
    Notification::fake();

    $vendor = Vendor::factory()->create();

    Password::broker('vendors')->sendResetLink(['email' => $vendor->user->email]);

    Notification::assertSentTo($vendor->user, VendorResetPasswordNotification::class, function ($notification) use ($vendor) {
        $mail = $notification->toMail($vendor->user);

        return str_contains($mail->actionUrl, '/vendor/reset-password/'.$notification->token);
    });
});
