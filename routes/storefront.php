<?php

use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('stores/{slug}', [StoreController::class, 'show'])->name('stores.show');
