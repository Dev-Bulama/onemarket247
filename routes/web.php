<?php

use App\Http\Controllers\Admin\LiveLocationDataController;
use App\Http\Controllers\Admin\LocationHistoryController;
use App\Http\Controllers\Admin\ProductTranslationExportController;
use App\Http\Controllers\AgentDocumentDownloadController;
use App\Http\Controllers\Delivery\DeliveryRequestAcceptController;
use App\Http\Controllers\Delivery\DeliveryTrackingController;
use App\Http\Controllers\InvoiceDownloadController;
use App\Http\Controllers\PackingSlipDownloadController;
use App\Http\Controllers\ProductDigitalFileDownloadController;
use App\Http\Controllers\Storefront\CurrencyController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\LocaleController;
use App\Http\Controllers\VendorDocumentDownloadController;
use Illuminate\Support\Facades\Route;

// track.visit is scoped to these storefront-facing routes only (see
// TrackSiteVisit's docblock) — never applied to the admin/vendor Filament
// panels or the API/mobile app.
Route::middleware('track.visit')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    require __DIR__.'/storefront.php';
});

Route::post('locale/{code}', [LocaleController::class, 'switch'])->name('locale.switch');
Route::post('currency/{code}', [CurrencyController::class, 'switch'])->name('currency.switch');

Route::get('vendor-documents/{vendorDocument}/download', VendorDocumentDownloadController::class)
    ->middleware('auth:admin,vendor')
    ->name('vendor-documents.download');

Route::get('agent-documents/{agentDocument}/download', AgentDocumentDownloadController::class)
    ->middleware('auth:admin')
    ->name('agent-documents.download');

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

Route::get('admin/location-tracking/data', LiveLocationDataController::class)
    ->middleware('auth:admin')
    ->name('admin.location-tracking.data');

Route::get('admin/location-tracking/{user}/history', LocationHistoryController::class)
    ->middleware('auth:admin')
    ->name('admin.location-tracking.history');

// No auth middleware, and deliberately no delivery-partner login system at
// all (see Priority 7's scope decision) — the unguessable per-partner
// `token` route parameter is the entire access control, exactly like the
// guest order-invoice route above.
Route::get('delivery-requests/{notification:token}', [DeliveryRequestAcceptController::class, 'show'])->name('delivery-requests.accept');
Route::post('delivery-requests/{notification:token}', [DeliveryRequestAcceptController::class, 'accept'])->name('delivery-requests.accept.store');
Route::get('deliveries/{assignment:tracking_token}', [DeliveryTrackingController::class, 'show'])->name('deliveries.track');
Route::post('deliveries/{assignment:tracking_token}', [DeliveryTrackingController::class, 'advance'])->name('deliveries.track.advance');

require __DIR__.'/auth.php';
require __DIR__.'/vendor.php';
require __DIR__.'/agent.php';
