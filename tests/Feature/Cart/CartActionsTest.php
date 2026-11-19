<?php

use App\Actions\Cart\AddCartItemAction;
use App\Actions\Cart\ApplyCouponAction;
use App\Actions\Cart\MergeGuestCartIntoCustomerCartAction;
use App\Actions\Cart\RemoveCouponAction;
use App\Actions\Cart\UpdateCartItemQuantityAction;
use App\Enums\CartStatus;
use App\Enums\CouponType;
use App\Enums\StockStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\Vendor;

test('adding a product creates a cart item with the current price', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['price' => 1500]);

    $item = app(AddCartItemAction::class)->handle($cart, $product, null, 2);

    expect($item->quantity)->toBe(2)
        ->and($item->unit_price)->toBe(1500)
        ->and($item->product_variation_id)->toBeNull();
});

test('adding the same product twice sums the quantity instead of duplicating the line', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 10, 'stock_status' => StockStatus::InStock]);

    app(AddCartItemAction::class)->handle($cart, $product, null, 2);
    app(AddCartItemAction::class)->handle($cart, $product, null, 3);

    expect($cart->items()->count())->toBe(1)
        ->and($cart->items()->first()->quantity)->toBe(5);
});

test('adding a simple product with no price set is rejected instead of crashing', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['price' => null]);

    expect(fn () => app(AddCartItemAction::class)->handle($cart, $product, null, 1))
        ->toThrow(RuntimeException::class);

    expect($cart->items()->count())->toBe(0);
});

test('adding a variable product without a variation is rejected', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->variable()->create();

    expect(fn () => app(AddCartItemAction::class)->handle($cart, $product, null, 1))
        ->toThrow(RuntimeException::class);
});

test('adding a variation that belongs to a different product is rejected', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->variable()->create();
    $otherProduct = Product::factory()->variable()->create();
    $variation = ProductVariation::factory()->create(['product_id' => $otherProduct->id]);

    expect(fn () => app(AddCartItemAction::class)->handle($cart, $product, $variation, 1))
        ->toThrow(RuntimeException::class);
});

test('adding more than available stock is rejected', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 2, 'stock_status' => StockStatus::InStock]);

    expect(fn () => app(AddCartItemAction::class)->handle($cart, $product, null, 3))
        ->toThrow(InsufficientStockException::class);
});

test('out of stock products cannot be added regardless of quantity', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 0, 'stock_status' => StockStatus::OutOfStock]);

    expect(fn () => app(AddCartItemAction::class)->handle($cart, $product, null, 1))
        ->toThrow(InsufficientStockException::class);
});

test('backorder products can be added beyond the cached stock quantity', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 0, 'stock_status' => StockStatus::OnBackorder]);

    $item = app(AddCartItemAction::class)->handle($cart, $product, null, 50);

    expect($item->quantity)->toBe(50);
});

test('unmanaged stock products have no quantity ceiling', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['manage_stock' => false]);

    $item = app(AddCartItemAction::class)->handle($cart, $product, null, 999);

    expect($item->quantity)->toBe(999);
});

test('updating quantity to zero removes the item', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 10, 'stock_status' => StockStatus::InStock]);
    $item = app(AddCartItemAction::class)->handle($cart, $product, null, 2);

    $result = app(UpdateCartItemQuantityAction::class)->handle($item, 0);

    expect($result)->toBeNull();
    expect($cart->items()->count())->toBe(0);
});

test('updating quantity refreshes the unit price to the current product price', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => true, 'stock_quantity' => 10, 'stock_status' => StockStatus::InStock]);
    $item = app(AddCartItemAction::class)->handle($cart, $product, null, 1);

    $product->update(['price' => 1200]);

    $updated = app(UpdateCartItemQuantityAction::class)->handle($item, 2);

    expect($updated->unit_price)->toBe(1200);
});

test('updating quantity beyond available stock is rejected', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 3, 'stock_status' => StockStatus::InStock]);
    $item = app(AddCartItemAction::class)->handle($cart, $product, null, 1);

    expect(fn () => app(UpdateCartItemQuantityAction::class)->handle($item, 10))
        ->toThrow(InsufficientStockException::class);
});

test('applying a valid coupon computes and stores the discount', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['price' => 10000]);
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    $coupon = Coupon::factory()->create(['code' => 'save10', 'type' => CouponType::Percentage, 'value' => 10]);

    $cartCoupon = app(ApplyCouponAction::class)->handle($cart, 'SAVE10');

    expect($cartCoupon->discount_amount)->toBe(1000)
        ->and($cartCoupon->code)->toBe('SAVE10');
});

test('an unknown or expired coupon is rejected', function () {
    $cart = Cart::factory()->create();
    Coupon::factory()->expired()->create(['code' => 'OLDCODE']);

    expect(fn () => app(ApplyCouponAction::class)->handle($cart, 'OLDCODE'))
        ->toThrow(RuntimeException::class);

    expect(fn () => app(ApplyCouponAction::class)->handle($cart, 'NOPE'))
        ->toThrow(RuntimeException::class);
});

test('a coupon below its minimum spend is rejected', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['price' => 500]);
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    Coupon::factory()->create(['code' => 'BIGSPEND', 'minimum_spend' => 5000]);

    expect(fn () => app(ApplyCouponAction::class)->handle($cart, 'BIGSPEND'))
        ->toThrow(RuntimeException::class);
});

test('the minimum spend error message is formatted in the store base currency, not hardcoded dollars', function () {
    Currency::query()->update(['is_default' => false]);
    Currency::factory()->create(['code' => 'NGN', 'symbol' => '₦', 'is_default' => true]);

    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['price' => 500]);
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    Coupon::factory()->create(['code' => 'BIGSPEND', 'minimum_spend' => 5000]);

    try {
        app(ApplyCouponAction::class)->handle($cart, 'BIGSPEND');
        $this->fail('Expected a RuntimeException to be thrown.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())
            ->toContain('₦50.00')
            ->not->toContain('$50.00');
    }
});

test('a vendor coupon only discounts that vendors items in the cart', function () {
    $vendorA = Vendor::factory()->create();
    $vendorB = Vendor::factory()->create();
    $productA = Product::factory()->create(['vendor_id' => $vendorA->id, 'price' => 10000]);
    $productB = Product::factory()->create(['vendor_id' => $vendorB->id, 'price' => 5000]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $productA, null, 1);
    app(AddCartItemAction::class)->handle($cart, $productB, null, 1);

    $coupon = Coupon::factory()->create(['code' => 'VENDORA10', 'vendor_id' => $vendorA->id, 'type' => CouponType::Percentage, 'value' => 10]);

    $cartCoupon = app(ApplyCouponAction::class)->handle($cart, 'VENDORA10');

    // 10% of vendor A's 10000 line only — vendor B's 5000 line is untouched.
    expect($cartCoupon->discount_amount)->toBe(1000);
});

test('a vendor coupon is rejected if the cart has none of that vendors items', function () {
    $vendorA = Vendor::factory()->create();
    $vendorB = Vendor::factory()->create();
    $productB = Product::factory()->create(['vendor_id' => $vendorB->id, 'price' => 5000]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $productB, null, 1);

    Coupon::factory()->create(['code' => 'VENDORA10', 'vendor_id' => $vendorA->id]);

    expect(fn () => app(ApplyCouponAction::class)->handle($cart, 'VENDORA10'))
        ->toThrow(RuntimeException::class);
});

test('a vendor coupons minimum spend is checked against that vendors items only', function () {
    $vendorA = Vendor::factory()->create();
    $vendorB = Vendor::factory()->create();
    $productA = Product::factory()->create(['vendor_id' => $vendorA->id, 'price' => 2000]);
    $productB = Product::factory()->create(['vendor_id' => $vendorB->id, 'price' => 20000]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $productA, null, 1);
    app(AddCartItemAction::class)->handle($cart, $productB, null, 1);

    // Cart total (22000) clears this minimum, but vendor A's own share (2000) does not.
    Coupon::factory()->create(['code' => 'VENDORMIN', 'vendor_id' => $vendorA->id, 'minimum_spend' => 5000]);

    expect(fn () => app(ApplyCouponAction::class)->handle($cart, 'VENDORMIN'))
        ->toThrow(RuntimeException::class);
});

test('applying a new coupon replaces any previously applied one', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['price' => 10000]);
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    Coupon::factory()->create(['code' => 'FIRST', 'value' => 10]);
    Coupon::factory()->create(['code' => 'SECOND', 'value' => 20]);

    app(ApplyCouponAction::class)->handle($cart, 'FIRST');
    app(ApplyCouponAction::class)->handle($cart, 'SECOND');

    expect($cart->coupon()->count())->toBe(1)
        ->and($cart->fresh()->coupon->code)->toBe('SECOND');
});

test('removing a coupon clears it from the cart', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['price' => 10000]);
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    Coupon::factory()->create(['code' => 'REMOVEME', 'value' => 10]);
    app(ApplyCouponAction::class)->handle($cart, 'REMOVEME');

    app(RemoveCouponAction::class)->handle($cart);

    expect($cart->fresh()->coupon)->toBeNull();
});

test('merging a guest cart into a customer cart sums matching lines and keeps distinct ones, then marks the guest cart merged', function () {
    $customer = User::factory()->create();
    $customerCart = $customer->carts()->create(['status' => CartStatus::Active]);
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();
    $customerCart->items()->create(['product_id' => $productA->id, 'quantity' => 1, 'unit_price' => 1000]);

    $guestCart = Cart::factory()->create();
    $guestCart->items()->create(['product_id' => $productA->id, 'quantity' => 2, 'unit_price' => 1000]);
    $guestCart->items()->create(['product_id' => $productB->id, 'quantity' => 1, 'unit_price' => 2000]);

    $merged = app(MergeGuestCartIntoCustomerCartAction::class)->handle($guestCart, $customer);

    expect($merged->items()->count())->toBe(2)
        ->and($merged->items()->where('product_id', $productA->id)->first()->quantity)->toBe(3)
        ->and($merged->items()->where('product_id', $productB->id)->first()->quantity)->toBe(1)
        ->and($guestCart->fresh()->status)->toBe(CartStatus::Merged)
        ->and($guestCart->fresh()->items()->count())->toBe(0);
});

test('merging carries the guest coupon over only if the customer has none applied', function () {
    $customer = User::factory()->create();
    $customerCart = $customer->carts()->create(['status' => CartStatus::Active]);
    $product = Product::factory()->create(['price' => 10000]);
    $customerCart->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000]);
    $customerCoupon = Coupon::factory()->create(['code' => 'CUSTOMERCODE', 'value' => 5]);
    app(ApplyCouponAction::class)->handle($customerCart, 'CUSTOMERCODE');

    $guestCart = Cart::factory()->create();
    $guestCart->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000]);
    $guestCoupon = Coupon::factory()->create(['code' => 'GUESTCODE', 'value' => 20]);
    app(ApplyCouponAction::class)->handle($guestCart, 'GUESTCODE');

    $merged = app(MergeGuestCartIntoCustomerCartAction::class)->handle($guestCart, $customer);

    expect($merged->coupon->code)->toBe('CUSTOMERCODE');
});
