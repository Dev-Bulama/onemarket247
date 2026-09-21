<?php

use App\Models\User;
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

test('requesting a reset for an unknown email shows the same generic message as a real send', function () {
    $vendor = Vendor::factory()->create();

    $knownResponse = $this->post('/vendor/forgot-password', ['email' => $vendor->user->email]);
    $unknownResponse = $this->post('/vendor/forgot-password', ['email' => 'no-such-vendor@example.com']);

    $knownResponse->assertSessionHas('status')->assertSessionDoesntHaveErrors();
    $unknownResponse->assertSessionHas('status')->assertSessionDoesntHaveErrors();

    expect(session()->get('status'))->not->toBeEmpty();
});

test('requesting a reset for a customer email (not a vendor) also shows the generic message, not an enumeration hint', function () {
    $customer = User::factory()->create();

    $response = $this->post('/vendor/forgot-password', ['email' => $customer->email]);

    $response->assertSessionHas('status')->assertSessionDoesntHaveErrors();
});
