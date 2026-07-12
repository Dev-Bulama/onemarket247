<?php

use App\Http\Controllers\Vendor\AuthenticatedSessionController;
use App\Http\Controllers\Vendor\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendor')->group(function () {
    Route::middleware('guest:vendor')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('vendor.login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::middleware('auth:vendor')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('vendor.dashboard');
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('vendor.logout');
    });
});
