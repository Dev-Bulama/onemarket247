<?php

use App\Http\Controllers\VendorDocumentDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('vendor-documents/{vendorDocument}/download', VendorDocumentDownloadController::class)
    ->middleware('auth:admin,vendor')
    ->name('vendor-documents.download');

require __DIR__.'/auth.php';
require __DIR__.'/vendor.php';
require __DIR__.'/storefront.php';
