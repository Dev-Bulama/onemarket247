<?php

use App\Http\Controllers\Agent\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('agent')->group(function () {
    Route::get('apply', [RegistrationController::class, 'create'])->name('agent.apply');
    Route::post('apply', [RegistrationController::class, 'store']);
    Route::view('apply/submitted', 'agent.onboarding.submitted')->name('agent.apply.submitted');
});
