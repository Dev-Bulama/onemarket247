<?php

use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\BrandController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CartCouponController;
use App\Http\Controllers\Storefront\CartItemController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CollectionController;
use App\Http\Controllers\Storefront\LocationController;
use App\Http\Controllers\Storefront\OrderTrackingController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\PaymentController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\QuestionController;
use App\Http\Controllers\Storefront\ReviewController;
use App\Http\Controllers\Storefront\ReviewVoteController;
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

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::post('products/{product:slug}/reviews', [ReviewController::class, 'store'])->name('products.reviews.store');
    Route::post('products/{product:slug}/questions', [QuestionController::class, 'store'])->name('products.questions.store');
    Route::post('reviews/{review}/helpful-vote', [ReviewVoteController::class, 'store'])->name('reviews.vote');
});

// Cart is guest+authenticated: no auth middleware. CartResolver identifies
// the guest cart via a signed cookie and the customer cart via the web
// guard, and merges the two on login (see App\Listeners\MergeGuestCartOnLogin).
Route::get('cart', [CartController::class, 'index'])->name('cart.index');
Route::post('cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
Route::patch('cart/items/{cartItem}', [CartItemController::class, 'update'])->name('cart.items.update');
Route::delete('cart/items/{cartItem}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');
Route::patch('cart/items/{cartItem}/save-for-later', [CartItemController::class, 'saveForLater'])->name('cart.items.save-for-later');
Route::patch('cart/items/{cartItem}/move-to-cart', [CartItemController::class, 'moveToCart'])->name('cart.items.move-to-cart');
Route::post('cart/coupon', [CartCouponController::class, 'store'])->name('cart.coupon.store');
Route::delete('cart/coupon', [CartCouponController::class, 'destroy'])->name('cart.coupon.destroy');

// Checkout is guest+authenticated too, same reasoning as cart above.
Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('checkout/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

// Payment routes are gated by OrderPolicy::view() (guest via public_id,
// same as the confirmation page above), not an auth guard, since a guest
// order's owner has no account to authenticate as.
Route::post('checkout/{order}/pay', [PaymentController::class, 'initialize'])->name('checkout.payment.initialize');
Route::get('checkout/{order}/pay/callback', [PaymentController::class, 'callback'])->name('checkout.payment.callback');

Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
Route::get('stores/{slug}', [StoreController::class, 'show'])->name('stores.show');

Route::get('contact', [PageController::class, 'contact'])->name('pages.contact');
Route::post('contact', [PageController::class, 'submitContact'])->middleware('throttle:5,1')->name('pages.contact.submit');
Route::get('faq', [PageController::class, 'faq'])->name('pages.faq');
Route::get('terms', [PageController::class, 'terms'])->name('pages.terms');
Route::get('privacy-policy', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('about-us', [PageController::class, 'aboutUs'])->name('pages.about-us');
Route::get('partnership', [PageController::class, 'partnership'])->name('pages.partnership');

Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

Route::post('delivery-location', [LocationController::class, 'switch'])->name('location.switch');

Route::get('track-order', [OrderTrackingController::class, 'show'])->name('pages.track-order');
