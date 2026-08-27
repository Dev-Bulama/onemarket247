<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CartCouponController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReferenceDataController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\StoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('sessions', [AuthController::class, 'sessions']);
            Route::delete('sessions/{tokenId}', [AuthController::class, 'destroySession']);
        });
    });

    // Unauthenticated by design — see PaymentWebhookController.
    Route::post('webhooks/payments/{gateway}', PaymentWebhookController::class)->name('api.webhooks.payments');

    // Public catalog browsing — generous throttle, no auth required, mirrors
    // the storefront's own web controllers query-for-query (see each
    // controller's docblock) so mobile and web can never disagree on what
    // a product/category/store/search result looks like.
    Route::middleware('throttle:120,1')->group(function () {
        Route::get('config', ConfigController::class);

        Route::get('languages', [ReferenceDataController::class, 'languages']);
        Route::get('currencies', [ReferenceDataController::class, 'currencies']);
        Route::get('countries', [ReferenceDataController::class, 'countries']);
        Route::get('countries/{country}/states', [ReferenceDataController::class, 'states']);
        Route::get('states/{state}/cities', [ReferenceDataController::class, 'cities']);

        Route::get('home', HomeController::class);

        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{slug}', [CategoryController::class, 'show']);

        Route::get('brands', [BrandController::class, 'index']);
        Route::get('brands/{slug}', [BrandController::class, 'show']);

        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{product:slug}', [ProductController::class, 'show']);

        Route::get('stores', [StoreController::class, 'index']);
        Route::get('stores/{slug}', [StoreController::class, 'show']);
        Route::get('stores/{slug}/products', [StoreController::class, 'products']);

        Route::get('search', SearchController::class);
    });

    // Cart — open to guests (identified by a client-persisted cart_token,
    // never a cookie — see CartResolver's docblock) and to Sanctum-
    // authenticated customers alike, exactly like the web cart.
    Route::middleware('throttle:120,1')->prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('merge', [CartController::class, 'merge']);

        Route::post('items', [CartItemController::class, 'store']);
        Route::patch('items/{cartItem}', [CartItemController::class, 'update']);
        Route::delete('items/{cartItem}', [CartItemController::class, 'destroy']);

        Route::post('coupons', [CartCouponController::class, 'store']);
        Route::delete('coupons', [CartCouponController::class, 'destroy']);
    });
});
