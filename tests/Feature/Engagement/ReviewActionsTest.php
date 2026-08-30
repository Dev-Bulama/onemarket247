<?php

use App\Actions\Review\ApproveReviewAction;
use App\Actions\Review\RejectReviewAction;
use App\Actions\Review\RespondToReviewAction;
use App\Actions\Review\SubmitReviewAction;
use App\Enums\ReviewStatus;
use App\Enums\UserType;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Notifications\ReviewRejectedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('submitting a review creates it as pending', function () {
    $product = Product::factory()->create();
    $customer = User::factory()->create(['user_type' => UserType::Customer]);

    $review = app(SubmitReviewAction::class)->handle($product, $customer, 5, 'Great', 'Loved it.');

    expect($review->status)->toBe(ReviewStatus::Pending)
        ->and($review->product_id)->toBe($product->id)
        ->and($review->customer_id)->toBe($customer->id);
});

test('submitting a review with photos attaches them to the review', function () {
    Storage::fake('public');
    $product = Product::factory()->create();
    $customer = User::factory()->create(['user_type' => UserType::Customer]);
    $images = [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')];

    $review = app(SubmitReviewAction::class)->handle($product, $customer, 5, 'Great', 'Loved it.', $images);

    expect($review->getMedia('images'))->toHaveCount(2);
});

test('a customer cannot submit a second review for the same product', function () {
    $product = Product::factory()->create();
    $customer = User::factory()->create(['user_type' => UserType::Customer]);

    app(SubmitReviewAction::class)->handle($product, $customer, 5, 'Great', 'Loved it.');

    expect(fn () => app(SubmitReviewAction::class)->handle($product, $customer, 3, 'Again', 'Second try.'))
        ->toThrow(RuntimeException::class);
});

test('approving a review sets status, reviewer, and timestamp', function () {
    $review = ProductReview::factory()->create();
    $admin = User::factory()->create();

    app(ApproveReviewAction::class)->handle($review, $admin);

    expect($review->fresh()->status)->toBe(ReviewStatus::Approved)
        ->and($review->fresh()->reviewed_by)->toBe($admin->id)
        ->and($review->fresh()->reviewed_at)->not->toBeNull();
});

test('rejecting a review sets the reason and notifies the customer', function () {
    Notification::fake();

    $review = ProductReview::factory()->create();
    $admin = User::factory()->create();

    app(RejectReviewAction::class)->handle($review, 'Contains inappropriate language.', $admin);

    expect($review->fresh()->status)->toBe(ReviewStatus::Rejected)
        ->and($review->fresh()->rejection_reason)->toBe('Contains inappropriate language.');

    Notification::assertSentTo($review->customer, ReviewRejectedNotification::class);
});

test('a vendor response is recorded with a timestamp', function () {
    $review = ProductReview::factory()->approved()->create();

    app(RespondToReviewAction::class)->handle($review, 'Thanks for the feedback!');

    expect($review->fresh()->vendor_response)->toBe('Thanks for the feedback!')
        ->and($review->fresh()->vendor_responded_at)->not->toBeNull();
});
