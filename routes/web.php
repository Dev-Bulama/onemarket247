<?php

use App\Http\Controllers\ProductDigitalFileDownloadController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\VendorDocumentDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('vendor-documents/{vendorDocument}/download', VendorDocumentDownloadController::class)
    ->middleware('auth:admin,vendor')
    ->name('vendor-documents.download');

Route::get('product-digital-files/{productDigitalFile}/download', ProductDigitalFileDownloadController::class)
    ->middleware('auth:admin,vendor')
    ->name('product-digital-files.download');

require __DIR__.'/auth.php';
require __DIR__.'/vendor.php';
require __DIR__.'/storefront.php';
