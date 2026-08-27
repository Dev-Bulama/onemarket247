<?php

use App\Enums\ReviewStatus;
use App\Models\Address;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductReview;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;

function apiCustomerToken(): array
{
    $user = User::factory()->create();
    $token = $user->createToken('t', ['customer:*'])->plainTextToken;

    return [$user, $token];
}

// -- Wishlist ---------------------------------------------------------

test('a customer can add, list, and remove a product from their wishlist', function () {
    [$user, $token] = apiCustomerToken();
    $product = Product::factory()->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/wishlist/{$product->id}")
        ->assertCreated();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/wishlist')
        ->assertOk()
        ->assertJsonPath('data.0.id', $product->id);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/wishlist/{$product->id}")
        ->assertOk();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/wishlist')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('wishlist requires authentication', function () {
    $this->getJson('/api/v1/wishlist')->assertUnauthorized();
});

test('a customer can add, list, and remove a product from their compare list', function () {
    [$user, $token] = apiCustomerToken();
    $product = Product::factory()->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/compare/{$product->id}")
        ->assertCreated();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/compare')
        ->assertOk()
        ->assertJsonPath('data.0.id', $product->id);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/compare/{$product->id}")
        ->assertOk();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/compare')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

// -- Addresses ----------------------------------------------------------

test('a customer can create, update, and delete their own address', function () {
    [$user, $token] = apiCustomerToken();
    $country = Country::factory()->create();

    $create = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/addresses', [
            'label' => 'Home',
            'full_name' => 'Jane Doe',
            'address_line_1' => '1 Main St',
            'country_id' => $country->id,
            'is_default_shipping' => true,
        ])->assertCreated();

    $addressId = $create->json('data.id');
    expect($create->json('data.is_default_shipping'))->toBeTrue();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/addresses/{$addressId}", [
            'label' => 'Office',
            'full_name' => 'Jane Doe',
            'address_line_1' => '2 Second St',
            'country_id' => $country->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.label', 'Office');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/addresses/{$addressId}")
        ->assertOk();

    expect(Address::find($addressId))->toBeNull();
});

test('a customer cannot delete another customer\'s address', function () {
    $owner = User::factory()->create();
    $country = Country::factory()->create();
    $address = $owner->addresses()->create(Address::factory()->raw(['country_id' => $country->id]));

    [$intruder, $token] = apiCustomerToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/addresses/{$address->id}")
        ->assertForbidden();
});

// -- Profile --------------------------------------------------------------

test('a customer can view and update their profile', function () {
    [$user, $token] = apiCustomerToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/v1/profile', ['name' => 'Updated Name', 'marketing_opt_in' => true])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.marketing_opt_in', true);

    expect($user->fresh()->name)->toBe('Updated Name');
});

test('a customer can change their password with the correct current password', function () {
    [$user, $token] = apiCustomerToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/profile/password', [
            'current_password' => 'password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
        ->assertOk();

    expect(Hash::check('new-secure-password', $user->fresh()->password))->toBeTrue();
});

test('changing password with the wrong current password fails', function () {
    [$user, $token] = apiCustomerToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
        ->assertStatus(422);
});

// -- Reviews ----------------------------------------------------------------

test('reviews index only shows approved reviews', function () {
    $product = Product::factory()->create();
    $approved = ProductReview::factory()->approved()->create(['product_id' => $product->id]);
    ProductReview::factory()->create(['product_id' => $product->id, 'status' => ReviewStatus::Pending]);

    $this->getJson("/api/v1/products/{$product->slug}/reviews")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $approved->id);
});

test('a customer can submit a review, which starts pending', function () {
    [$user, $token] = apiCustomerToken();
    $product = Product::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/products/{$product->slug}/reviews", [
            'rating' => 5,
            'title' => 'Great!',
            'body' => 'Loved it.',
        ])->assertCreated();

    expect(ProductReview::find($response->json('data.id'))->status)->toBe(ReviewStatus::Pending);
});

test('a customer cannot review the same product twice', function () {
    [$user, $token] = apiCustomerToken();
    $product = Product::factory()->create();
    ProductReview::factory()->create(['product_id' => $product->id, 'customer_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 4, 'body' => 'Again'])
        ->assertStatus(422);
});

test('marking a review helpful increments its count once per customer', function () {
    [$user, $token] = apiCustomerToken();
    $review = ProductReview::factory()->approved()->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/reviews/{$review->id}/helpful")
        ->assertOk()
        ->assertJsonPath('data.helpful_count', 1);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/reviews/{$review->id}/helpful")
        ->assertStatus(422);
});

// -- Questions ----------------------------------------------------------

test('a customer can ask a question, and the vendor can answer it', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    [$customer, $customerToken] = apiCustomerToken();

    $ask = $this->withHeader('Authorization', "Bearer {$customerToken}")
        ->postJson("/api/v1/products/{$product->slug}/questions", ['question' => 'Does this ship to Lagos?'])
        ->assertCreated();

    $questionId = $ask->json('data.id');

    // Sanctum's guard caches the resolved user on itself for the lifetime
    // of the test's container — without this, the second call below would
    // still authenticate as the customer from the request just above.
    app('auth')->forgetGuards();

    $vendorToken = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$vendorToken}")
        ->postJson("/api/v1/questions/{$questionId}/answers", ['answer' => 'Yes, it does!'])
        ->assertCreated()
        ->assertJsonPath('data.is_answered', true)
        ->assertJsonPath('data.answers.0.answer', 'Yes, it does!');

    $this->getJson("/api/v1/products/{$product->slug}/questions")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('a customer cannot answer a question on a product they don\'t sell', function () {
    $product = Product::factory()->create();
    $question = ProductQuestion::factory()->create(['product_id' => $product->id]);

    [$otherCustomer, $token] = apiCustomerToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/questions/{$question->id}/answers", ['answer' => 'Not mine to answer'])
        ->assertForbidden();
});
