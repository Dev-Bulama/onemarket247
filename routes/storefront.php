<?php

use App\Http\Controllers\Storefront\BrandController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CollectionController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('shop', [ShopController::class, 'index'])->name('shop.index');

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{category:slug}/{subcategory:slug}', [CategoryController::class, 'show'])->name('categories.show-subcategory')->withoutScopedBindings();
Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');

Route::get('collections/{collection:slug}', [CollectionController::class, 'show'])->name('collections.show');

Route::get('search', [SearchController::class, 'index'])->name('search.index');

Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
Route::get('stores/{slug}', [StoreController::class, 'show'])->name('stores.show');

Route::get('contact', [PageController::class, 'contact'])->name('pages.contact');
Route::post('contact', [PageController::class, 'submitContact'])->middleware('throttle:5,1')->name('pages.contact.submit');
Route::get('faq', [PageController::class, 'faq'])->name('pages.faq');
Route::get('terms', [PageController::class, 'terms'])->name('pages.terms');
Route::get('privacy-policy', [PageController::class, 'privacy'])->name('pages.privacy');
