<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\CompareController;
use App\Http\Controllers\Account\WishlistController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\DeviceSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:web')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor.login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');

    Route::get('auth/social/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('auth/social/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
});

Route::middleware('auth:web')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('verified')->group(function () {
        Route::get('account', [AccountController::class, 'dashboard'])->name('account.dashboard');
        Route::get('account/security', [AccountController::class, 'security'])->name('account.security');

        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::delete('account/sessions/other', [DeviceSessionController::class, 'destroyOthers'])->name('account.sessions.destroy-others');
        Route::delete('account/sessions/{deviceSession}', [DeviceSessionController::class, 'destroy'])->name('account.sessions.destroy');

        Route::get('two-factor-authentication', [TwoFactorAuthenticationController::class, 'create'])->name('two-factor.show');
        Route::post('two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])->name('two-factor.confirm');
        Route::delete('two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.disable');

        Route::get('account/profile', [AccountController::class, 'editProfile'])->name('account.profile.edit');
        Route::put('account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');

        Route::get('account/addresses', [AddressController::class, 'index'])->name('account.addresses.index');
        Route::get('account/addresses/create', [AddressController::class, 'create'])->name('account.addresses.create');
        Route::post('account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
        Route::get('account/addresses/{address}/edit', [AddressController::class, 'edit'])->name('account.addresses.edit');
        Route::put('account/addresses/{address}', [AddressController::class, 'update'])->name('account.addresses.update');
        Route::delete('account/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');

        Route::get('account/wishlist', [WishlistController::class, 'index'])->name('account.wishlist.index');
        Route::post('account/wishlist/{product}', [WishlistController::class, 'store'])->name('account.wishlist.store');
        Route::delete('account/wishlist/{product}', [WishlistController::class, 'destroy'])->name('account.wishlist.destroy');

        Route::get('account/compare', [CompareController::class, 'index'])->name('account.compare.index');
        Route::post('account/compare/{product}', [CompareController::class, 'store'])->name('account.compare.store');
        Route::delete('account/compare/{product}', [CompareController::class, 'destroy'])->name('account.compare.destroy');
    });
});
