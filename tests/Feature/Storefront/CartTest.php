<?php

use App\Enums\CartStatus;
use App\Enums\StockStatus;
use App\Enums\UserType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;

test('guest can view an empty cart page', function () {
    $this->get(route('cart.index'))->assertOk()->assertSee('Your cart is empty');
});

test('a guest cart persists across requests via the cart cookie', function () {
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => true, 'stock_quantity' => 10, 'stock_status' => StockStatus::InStock]);

    $response = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 2]);
    $response->assertRedirect();

    $token = $response->getCookie('cart_token')->getValue();
    expect($token)->not->toBeNull();

    $this->withCookie('cart_token', $token)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertSee($product->name)
        ->assertSee('2');
});

test('a different guest (no matching cookie) sees an empty cart', function () {
    $product = Product::factory()->create();

    $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);

    // fresh request, no cookie carried over
    $this->get(route('cart.index'))->assertSee('Your cart is empty');
});

test('an authenticated customer cart is identified by the user, not a cookie', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer]);
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 5, 'stock_status' => StockStatus::InStock]);

    $this->actingAs($user)->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1])->assertRedirect();

    $this->actingAs($user)->get(route('cart.index'))->assertOk()->assertSee($product->name);

    $cart = $user->carts()->where('status', CartStatus::Active)->first();
    expect($cart)->not->toBeNull();
    expect($cart->items()->count())->toBe(1);
});

test('adding beyond available stock is rejected with a validation error', function () {
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 2, 'stock_status' => StockStatus::InStock]);

    $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 5])
        ->assertSessionHasErrors('quantity');
});

test('items from different vendors appear as separate groups on the cart page', function () {
    $vendorA = Vendor::factory()->create();
    Store::factory()->for($vendorA)->create(['name' => 'Vendor A Store']);
    $vendorB = Vendor::factory()->create();
    Store::factory()->for($vendorB)->create(['name' => 'Vendor B Store']);
    $productA = Product::factory()->create(['vendor_id' => $vendorA->id]);
    $productB = Product::factory()->create(['vendor_id' => $vendorB->id]);

    $response = $this->post(route('cart.items.store'), ['product_id' => $productA->id, 'quantity' => 1]);
    $token = $response->getCookie('cart_token')->getValue();

    $this->withCookie('cart_token', $token)->post(route('cart.items.store'), ['product_id' => $productB->id, 'quantity' => 1]);

    $this->withCookie('cart_token', $token)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertSee('Vendor A Store')
        ->assertSee('Vendor B Store');
});

test('a stale price banner appears once the product price changes after adding', function () {
    $product = Product::factory()->create(['price' => 1000]);

    $response = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $response->getCookie('cart_token')->getValue();

    $product->update(['price' => 1500]);

    $this->withCookie('cart_token', $token)
        ->get(route('cart.index'))
        ->assertSee('Price changed');
});

test('an out of stock cart item is flagged on the cart page', function () {
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 5, 'stock_status' => StockStatus::InStock]);

    $response = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $response->getCookie('cart_token')->getValue();

    $product->update(['stock_status' => StockStatus::OutOfStock, 'stock_quantity' => 0]);

    $this->withCookie('cart_token', $token)
        ->get(route('cart.index'))
        ->assertSee('No longer in stock');
});

test('an item can be saved for later and moved back to the cart', function () {
    $product = Product::factory()->create();

    $response = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $response->getCookie('cart_token')->getValue();
    $itemId = CartItem::first()->id;

    $this->withCookie('cart_token', $token)
        ->patch(route('cart.items.save-for-later', $itemId))
        ->assertRedirect();

    $this->withCookie('cart_token', $token)
        ->get(route('cart.index'))
        ->assertSee('Saved for later')
        ->assertSee('Your cart is empty');

    $this->withCookie('cart_token', $token)
        ->patch(route('cart.items.move-to-cart', $itemId))
        ->assertRedirect();

    expect(CartItem::find($itemId)->saved_for_later)->toBeFalse();
});

test('an item can be removed from the cart', function () {
    $product = Product::factory()->create();

    $response = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $response->getCookie('cart_token')->getValue();
    $itemId = CartItem::first()->id;

    $this->withCookie('cart_token', $token)
        ->delete(route('cart.items.destroy', $itemId))
        ->assertRedirect();

    expect(CartItem::find($itemId))->toBeNull();
});

test('a guest cannot mutate a cart item belonging to a different guest cart', function () {
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();

    $this->post(route('cart.items.store'), ['product_id' => $productA->id, 'quantity' => 1]);
    $itemA = CartItem::first();

    $responseB = $this->post(route('cart.items.store'), ['product_id' => $productB->id, 'quantity' => 1]);
    $tokenB = $responseB->getCookie('cart_token')->getValue();

    $this->withCookie('cart_token', $tokenB)
        ->delete(route('cart.items.destroy', $itemA))
        ->assertForbidden();

    expect(CartItem::find($itemA->id))->not->toBeNull();
});

test('a coupon can be applied and removed through the cart page', function () {
    $product = Product::factory()->create(['price' => 10000]);
    Coupon::factory()->create(['code' => 'TESTCODE', 'value' => 10]);

    $response = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $response->getCookie('cart_token')->getValue();

    $this->withCookie('cart_token', $token)
        ->post(route('cart.coupon.store'), ['code' => 'testcode'])
        ->assertRedirect();

    $this->withCookie('cart_token', $token)
        ->get(route('cart.index'))
        ->assertSee('TESTCODE')
        ->assertSee('90.00');

    $this->withCookie('cart_token', $token)
        ->delete(route('cart.coupon.destroy'))
        ->assertRedirect();

    $this->withCookie('cart_token', $token)
        ->get(route('cart.index'))
        ->assertDontSee('TESTCODE');
});

test('an invalid coupon code shows a validation error instead of applying', function () {
    $product = Product::factory()->create();
    $response = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $response->getCookie('cart_token')->getValue();

    $this->withCookie('cart_token', $token)
        ->post(route('cart.coupon.store'), ['code' => 'DOES-NOT-EXIST'])
        ->assertSessionHasErrors('code');
});

test('guest cart merges into the customer cart on login without item loss or duplication', function () {
    $productA = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 10, 'stock_status' => StockStatus::InStock]);
    $productB = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 10, 'stock_status' => StockStatus::InStock]);
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now(), 'password' => bcrypt('password')]);

    $response = $this->post(route('cart.items.store'), ['product_id' => $productA->id, 'quantity' => 1]);
    $token = $response->getCookie('cart_token')->getValue();

    $this->withCookie('cart_token', $token)
        ->post(route('cart.items.store'), ['product_id' => $productB->id, 'quantity' => 3]);

    $this->withCookie('cart_token', $token)
        ->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $customerCart = $user->fresh()->carts()->where('status', CartStatus::Active)->first();
    expect($customerCart)->not->toBeNull();
    expect($customerCart->items()->count())->toBe(2);
    expect($customerCart->items()->where('product_id', $productA->id)->first()->quantity)->toBe(1);
    expect($customerCart->items()->where('product_id', $productB->id)->first()->quantity)->toBe(3);
});

test('simple product page shows a working add to cart form', function () {
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 5, 'stock_status' => StockStatus::InStock]);

    $this->get(route('products.show', $product))->assertOk()->assertSee('Add to cart');

    $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1])->assertRedirect();
});

test('an out of stock simple product hides the add to cart form', function () {
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 0, 'stock_status' => StockStatus::OutOfStock]);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertDontSee('Add to cart')
        ->assertSee('currently out of stock');
});

test('a variable product page offers an option selector that can be added to cart', function () {
    $product = Product::factory()->variable()->create();
    $variation = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'manage_stock' => true,
        'stock_quantity' => 3,
        'stock_status' => StockStatus::InStock,
        'is_active' => true,
    ]);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Add to cart')
        ->assertSee('Choose an option');

    $this->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'product_variation_id' => $variation->id,
        'quantity' => 1,
    ])->assertRedirect();
});

test('browsing as a fresh guest never creates a cart row', function () {
    $this->get(route('home'))->assertOk();
    $this->get(route('shop.index'))->assertOk();

    expect(Cart::count())->toBe(0);
});
