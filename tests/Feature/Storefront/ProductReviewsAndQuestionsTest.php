<?php

use App\Enums\ReviewStatus;
use App\Enums\UserType;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductReview;
use App\Models\User;

test('the product page shows approved reviews but hides pending and rejected ones', function () {
    $product = Product::factory()->create();
    ProductReview::factory()->for($product)->approved()->create(['body' => 'Great product body text']);
    ProductReview::factory()->for($product)->create(['body' => 'Pending body text']);
    ProductReview::factory()->for($product)->rejected()->create(['body' => 'Rejected body text']);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Great product body text')
        ->assertDontSee('Pending body text')
        ->assertDontSee('Rejected body text');
});

test('a guest is redirected to login when trying to submit a review or question', function () {
    $product = Product::factory()->create();

    $this->post(route('products.reviews.store', $product), ['rating' => 5, 'body' => 'Nice'])
        ->assertRedirect(route('login'));

    $this->post(route('products.questions.store', $product), ['question' => 'Does it work?'])
        ->assertRedirect(route('login'));
});

test('a customer can submit a review that starts pending, and cannot submit a second one', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $product = Product::factory()->create();

    $this->actingAs($user)->post(route('products.reviews.store', $product), [
        'rating' => 4,
        'title' => 'Pretty good',
        'body' => 'Worked well for me.',
    ])->assertRedirect();

    $review = ProductReview::where('product_id', $product->id)->where('customer_id', $user->id)->first();
    expect($review)->not->toBeNull()
        ->and($review->status)->toBe(ReviewStatus::Pending);

    $this->actingAs($user)->post(route('products.reviews.store', $product), [
        'rating' => 3,
        'body' => 'Trying again',
    ])->assertSessionHasErrors('review');
});

test('a customer can ask a question on a product', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $product = Product::factory()->create();

    $this->actingAs($user)->post(route('products.questions.store', $product), [
        'question' => 'Does this come in blue?',
    ])->assertRedirect();

    expect(ProductQuestion::where('product_id', $product->id)->where('customer_id', $user->id)->exists())->toBeTrue();
});

test('answered questions and their answers are shown on the product page', function () {
    $product = Product::factory()->create();
    $question = ProductQuestion::factory()->for($product)->create(['question' => 'Is it waterproof?', 'is_answered' => true]);
    $question->answers()->create([
        'answered_by' => $product->vendor->user_id,
        'answer' => 'Yes, fully waterproof.',
    ]);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Is it waterproof?')
        ->assertSee('Yes, fully waterproof.');
});

test('a customer can vote a review helpful only once', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $product = Product::factory()->create();
    $review = ProductReview::factory()->for($product)->approved()->create();

    $this->actingAs($user)->post(route('reviews.vote', $review))->assertRedirect();
    expect($review->fresh()->helpful_count)->toBe(1);

    $this->actingAs($user)->post(route('reviews.vote', $review))->assertRedirect();
    expect($review->fresh()->helpful_count)->toBe(1);
});

test('voting on a non-approved review is not allowed', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $review = ProductReview::factory()->create();

    $this->actingAs($user)->post(route('reviews.vote', $review))->assertNotFound();
});
