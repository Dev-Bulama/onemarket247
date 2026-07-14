<?php

use App\Http\Controllers\Admin\ProductTranslationExportController;
use App\Http\Controllers\InvoiceDownloadController;
use App\Http\Controllers\PackingSlipDownloadController;
use App\Http\Controllers\ProductDigitalFileDownloadController;
use App\Http\Controllers\Storefront\CurrencyController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\LocaleController;
use App\Http\Controllers\VendorDocumentDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('locale/{code}', [LocaleController::class, 'switch'])->name('locale.switch');
Route::post('currency/{code}', [CurrencyController::class, 'switch'])->name('currency.switch');

Route::get('vendor-documents/{vendorDocument}/download', VendorDocumentDownloadController::class)
    ->middleware('auth:admin,vendor')
    ->name('vendor-documents.download');

Route::get('product-digital-files/{productDigitalFile}/download', ProductDigitalFileDownloadController::class)
    ->middleware('auth:admin,vendor')
    ->name('product-digital-files.download');

// No auth middleware: OrderPolicy::view() itself allows a guest order to
// be viewed by anyone holding the unguessable link, exactly like the
// checkout confirmation page.
Route::get('orders/{order}/invoice', InvoiceDownloadController::class)->name('orders.invoice');

Route::get('packing-slips/{vendorOrder}/download', PackingSlipDownloadController::class)
    ->middleware('auth:admin,vendor')
    ->name('packing-slips.download');

Route::get('admin/translation-report/export', ProductTranslationExportController::class)
    ->middleware('auth:admin')
    ->name('admin.translation-report.export');

require __DIR__.'/auth.php';
require __DIR__.'/vendor.php';
require __DIR__.'/storefront.php';
