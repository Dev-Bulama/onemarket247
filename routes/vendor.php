<?php

use App\Http\Controllers\Vendor\AuthenticatedSessionController;
use App\Http\Controllers\Vendor\NewPasswordController;
use App\Http\Controllers\Vendor\PasswordResetLinkController;
use App\Http\Controllers\Vendor\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendor')->group(function () {
    Route::get('register', [RegistrationController::class, 'create'])->name('vendor.register');
    Route::post('register', [RegistrationController::class, 'store']);
    Route::view('register/submitted', 'vendor.onboarding.submitted')->name('vendor.register.submitted');

    Route::middleware('guest:vendor')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('vendor.login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('vendor.password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('vendor.password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('vendor.password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('vendor.password.store');
    });

    Route::middleware('auth:vendor')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('vendor.logout');

        // Filament's own user-menu "log out" link always resolves
        // filament.vendor.auth.logout (see VendorPanelProvider — the panel
        // only registers a login page to satisfy Filament's internal
        // getLoginUrl() plumbing, see PanelLoginRedirect). Same controller
        // action under the name Filament's UI expects, so the topbar
        // "log out" button works without a second login system.
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('filament.vendor.auth.logout');
    });
});
