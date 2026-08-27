<?php

use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    Currency::factory()->create(['code' => 'NGN', 'is_default' => true]);
});

test('a guest can add an item to the cart without authenticating and gets back a guest_token', function () {
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => false]);

    $response = $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertCreated();

    $guestToken = $response->json('data.guest_token');

    expect($guestToken)->not->toBeNull();
    $response->assertJsonPath('data.items.0.product.id', $product->id)
        ->assertJsonPath('data.items.0.quantity', 2);
});

test('replaying the same guest_token on later calls returns the same cart', function () {
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => false]);

    $first = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertCreated();
    $token = $first->json('data.guest_token');

    $this->getJson("/api/v1/cart?cart_token={$token}")
        ->assertOk()
        ->assertJsonCount(1, 'data.items');
});

test('two different guests never see each other\'s cart', function () {
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => false]);

    $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertCreated();

    $this->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonCount(0, 'data.items');
});

test('an authenticated customer\'s cart is resolved from their token, no cart_token needed', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['customer:*'])->plainTextToken;
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => false]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 3])
        ->assertCreated()
        ->assertJsonPath('data.guest_token', null);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity', 3);
});

test('a cart item quantity can be updated and removed', function () {
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => false]);

    $add = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertCreated();
    $token = $add->json('data.guest_token');
    $itemId = $add->json('data.items.0.id');

    $this->patchJson("/api/v1/cart/items/{$itemId}?cart_token={$token}", ['quantity' => 5])
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity', 5);

    $this->deleteJson("/api/v1/cart/items/{$itemId}?cart_token={$token}")
        ->assertOk()
        ->assertJsonCount(0, 'data.items');
});

test('a guest cannot update or delete an item belonging to a different guest\'s cart', function () {
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => false]);

    $add = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertCreated();
    $itemId = $add->json('data.items.0.id');

    // No cart_token supplied here — resolves to a brand-new, different guest cart.
    $this->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 2])->assertForbidden();
    $this->deleteJson("/api/v1/cart/items/{$itemId}")->assertForbidden();
});

test('a valid coupon can be applied and removed from the cart', function () {
    Coupon::factory()->create(['code' => 'TESTCODE', 'value' => 10]);
    $product = Product::factory()->create(['price' => 100000, 'manage_stock' => false]);

    $add = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertCreated();
    $token = $add->json('data.guest_token');

    $this->postJson("/api/v1/cart/coupons?cart_token={$token}", ['code' => 'testcode'])
        ->assertOk()
        ->assertJsonPath('data.coupon.code', 'TESTCODE');

    $this->deleteJson("/api/v1/cart/coupons?cart_token={$token}")
        ->assertOk()
        ->assertJsonPath('data.coupon', null);
});

test('an invalid coupon code is rejected with a clear error, not a 500', function () {
    $product = Product::factory()->create(['price' => 100000, 'manage_stock' => false]);
    $add = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertCreated();
    $token = $add->json('data.guest_token');

    $this->postJson("/api/v1/cart/coupons?cart_token={$token}", ['code' => 'DOES-NOT-EXIST'])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'COUPON_ERROR');
});

test('merging a guest cart into a newly authenticated customer folds both into one cart', function () {
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => false]);
    $add = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertCreated();
    $guestToken = $add->json('data.guest_token');

    $user = User::factory()->create();
    $bearerToken = $user->createToken('test', ['customer:*'])->plainTextToken;

    $otherProduct = Product::factory()->create(['price' => 2000, 'manage_stock' => false]);
    $this->withHeader('Authorization', "Bearer {$bearerToken}")
        ->postJson('/api/v1/cart/items', ['product_id' => $otherProduct->id, 'quantity' => 1])
        ->assertCreated();

    $this->withHeader('Authorization', "Bearer {$bearerToken}")
        ->postJson('/api/v1/cart/merge', ['guest_token' => $guestToken])
        ->assertOk()
        ->assertJsonCount(2, 'data.items');
});

test('merging requires authentication', function () {
    $this->postJson('/api/v1/cart/merge', ['guest_token' => 'whatever'])->assertUnauthorized();
});
