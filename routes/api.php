<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\BootstrapController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CartCouponController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CompareController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\ReferenceDataController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\Vendor\EarningsController as VendorEarningsController;
use App\Http\Controllers\Api\V1\Vendor\InventoryController as VendorInventoryController;
use App\Http\Controllers\Api\V1\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Api\V1\Vendor\StoreController as VendorStoreController;
use App\Http\Controllers\Api\V1\Vendor\StoreStaffController;
use App\Http\Controllers\Api\V1\Vendor\SubscriptionController;
use App\Http\Controllers\Api\V1\Vendor\VendorApplicationController;
use App\Http\Controllers\Api\V1\Vendor\VendorDocumentController;
use App\Http\Controllers\Api\V1\Vendor\VendorOrderController;
use App\Http\Controllers\Api\V1\Vendor\WithdrawalController as VendorWithdrawalController;
use App\Http\Controllers\Api\V1\WishlistController;
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

    // Public vendor onboarding — someone applying to become a vendor has no
    // account yet, so this can't live in the auth:sanctum-gated vendor
    // group below. Throttled like forgot-password: an unauthenticated
    // write with real cost (documents get stored, a vendor may get
    // auto-provisioned).
    Route::post('vendor/apply', [VendorApplicationController::class, 'store'])->middleware('throttle:5,1');

    // Public catalog browsing — generous throttle, no auth required, mirrors
    // the storefront's own web controllers query-for-query (see each
    // controller's docblock) so mobile and web can never disagree on what
    // a product/category/store/search result looks like.
    Route::middleware('throttle:120,1')->group(function () {
        Route::get('bootstrap', BootstrapController::class);
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

        Route::get('products/{product:slug}/reviews', [ReviewController::class, 'index']);
        Route::get('products/{product:slug}/questions', [QuestionController::class, 'index']);

        Route::get('blog', [BlogController::class, 'index']);
        Route::get('blog/{post:slug}', [BlogController::class, 'show']);

        Route::get('pages/about-us', [PageController::class, 'aboutUs']);
        Route::get('pages/partnership', [PageController::class, 'partnership']);
        Route::get('pages/privacy', [PageController::class, 'privacy']);
        Route::get('pages/terms', [PageController::class, 'terms']);
        Route::get('pages/faq', [PageController::class, 'faq']);
    });

    Route::post('contact', [PageController::class, 'contact'])->middleware('throttle:5,1');

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

    // Checkout — open to guests and Sanctum-authenticated customers alike,
    // same as cart above.
    Route::middleware('throttle:60,1')->prefix('checkout')->group(function () {
        Route::post('init', [CheckoutController::class, 'init']);
        Route::post('complete', [CheckoutController::class, 'complete']);
        Route::get('{checkoutSessionKey}/status', [CheckoutController::class, 'status']);
    });

    // Orders — {order} binds by public_id (Order::getRouteKeyName()), the
    // same unguessable UUID a guest order's link already relies on for
    // authorization (see OrderController::show's docblock); only the
    // index (a real customer's order history) requires auth:sanctum.
    Route::middleware('throttle:60,1')->prefix('orders')->group(function () {
        Route::middleware('auth:sanctum')->get('/', [OrderController::class, 'index']);
        Route::get('{order}', [OrderController::class, 'show']);
        Route::get('{order}/track', [OrderController::class, 'track']);
        Route::post('{order}/cancel', [OrderController::class, 'cancel']);
    });

    Route::middleware('throttle:30,1')->prefix('payments')->group(function () {
        Route::post('{order}/initialize', [PaymentController::class, 'initialize']);
        Route::post('{order}/verify', [PaymentController::class, 'verify']);
    });

    // Account features — all require a real account, unlike cart/checkout;
    // gating the whole group behind auth:sanctum means every controller
    // here can use $request->user() / Gate::authorize() exactly like their
    // web equivalents (the middleware makes "sanctum" the default guard
    // for the rest of the request) instead of the explicit-guard dance
    // cart/checkout need to stay guest-accessible.
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::post('wishlist/{product}', [WishlistController::class, 'store']);
        Route::delete('wishlist/{product}', [WishlistController::class, 'destroy']);

        Route::get('compare', [CompareController::class, 'index']);
        Route::post('compare/{product}', [CompareController::class, 'store']);
        Route::delete('compare/{product}', [CompareController::class, 'destroy']);

        Route::get('addresses', [AddressController::class, 'index']);
        Route::post('addresses', [AddressController::class, 'store']);
        Route::patch('addresses/{address}', [AddressController::class, 'update']);
        Route::delete('addresses/{address}', [AddressController::class, 'destroy']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::patch('profile', [ProfileController::class, 'update']);
        Route::post('profile/password', [ProfileController::class, 'updatePassword']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);

        Route::post('products/{product:slug}/reviews', [ReviewController::class, 'store']);
        Route::post('reviews/{review}/helpful', [ReviewController::class, 'markHelpful']);

        Route::post('products/{product:slug}/questions', [QuestionController::class, 'store']);
        Route::post('questions/{question}/answers', [QuestionController::class, 'answer']);

        Route::post('device-tokens', [DeviceTokenController::class, 'store']);
        Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);
    });

    // Vendor API — a vendor owner or active store staff managing their own
    // store from the app. vendor.access mirrors User::canAccessPanel('vendor')
    // exactly (see EnsureVendorAccess), so anyone who could open the
    // Filament vendor panel can use this, and no one else.
    Route::middleware(['auth:sanctum', 'vendor.access', 'throttle:60,1'])->prefix('vendor')->group(function () {
        Route::get('store', [VendorStoreController::class, 'show']);
        Route::patch('store', [VendorStoreController::class, 'update']);

        Route::get('products', [VendorProductController::class, 'index']);
        Route::post('products', [VendorProductController::class, 'store']);
        Route::get('products/{product}', [VendorProductController::class, 'show']);
        Route::patch('products/{product}', [VendorProductController::class, 'update']);
        Route::delete('products/{product}', [VendorProductController::class, 'destroy']);

        Route::get('inventory', [VendorInventoryController::class, 'index']);
        Route::patch('inventory/{warehouseStock}', [VendorInventoryController::class, 'adjust']);

        Route::get('orders', [VendorOrderController::class, 'index']);
        Route::get('orders/{vendorOrder}', [VendorOrderController::class, 'show']);
        Route::patch('orders/{vendorOrder}/status', [VendorOrderController::class, 'updateStatus']);
        Route::post('orders/{vendorOrder}/cancel', [VendorOrderController::class, 'cancel']);

        Route::get('earnings', [VendorEarningsController::class, 'summary']);
        Route::get('earnings/transactions', [VendorEarningsController::class, 'transactions']);

        Route::get('withdrawals', [VendorWithdrawalController::class, 'index']);
        Route::post('withdrawals', [VendorWithdrawalController::class, 'store']);
        Route::post('withdrawals/methods', [VendorWithdrawalController::class, 'addMethod']);
        Route::post('withdrawals/{withdrawal}/cancel', [VendorWithdrawalController::class, 'cancel']);

        Route::get('staff', [StoreStaffController::class, 'index']);
        Route::post('staff', [StoreStaffController::class, 'store']);
        Route::patch('staff/{storeStaff}', [StoreStaffController::class, 'update']);
        Route::delete('staff/{storeStaff}', [StoreStaffController::class, 'destroy']);

        Route::get('subscription', [SubscriptionController::class, 'index']);
        Route::post('subscription/switch', [SubscriptionController::class, 'switchTo']);

        Route::get('documents', [VendorDocumentController::class, 'index']);
        Route::post('documents', [VendorDocumentController::class, 'store']);
    });
});
