<?php

use App\Actions\Product\ApproveProductAction;
use App\Actions\Product\RejectProductAction;
use App\Actions\Product\SubmitProductForApprovalAction;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ProductRejectedNotification;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    (new SettingsSeeder)->run();
});

test('submitting a draft product parks it in pending approval by default', function () {
    $product = Product::factory()->draft()->create();

    $result = app(SubmitProductForApprovalAction::class)->handle($product);

    expect($result->status)->toBe(ProductStatus::PendingApproval)
        ->and($result->published_at)->toBeNull();
});

test('submitting a product in automatic approval mode publishes it immediately', function () {
    Setting::where('key', 'products.approval_mode')->update(['value' => 'automatic']);

    $product = Product::factory()->draft()->create();

    $result = app(SubmitProductForApprovalAction::class)->handle($product);

    expect($result->status)->toBe(ProductStatus::Published)
        ->and($result->published_at)->not->toBeNull();
});

test('approving a pending product publishes it and records the reviewer', function () {
    $product = Product::factory()->pendingApproval()->create();
    $admin = User::factory()->admin()->create();

    $result = app(ApproveProductAction::class)->handle($product, $admin);

    expect($result->status)->toBe(ProductStatus::Published)
        ->and($result->published_at)->not->toBeNull()
        ->and($result->reviewed_by)->toBe($admin->id)
        ->and($result->rejection_reason)->toBeNull();
});

test('approving a previously published product does not overwrite its published_at', function () {
    $product = Product::factory()->create();
    $originalPublishedAt = $product->published_at;

    $result = app(ApproveProductAction::class)->handle($product);

    expect($result->published_at->equalTo($originalPublishedAt))->toBeTrue();
});

test('rejecting a pending product records the reason and notifies the vendor', function () {
    Notification::fake();

    $product = Product::factory()->pendingApproval()->create();
    $admin = User::factory()->admin()->create();

    $result = app(RejectProductAction::class)->handle($product, 'Poor image quality', $admin);

    expect($result->status)->toBe(ProductStatus::Rejected)
        ->and($result->rejection_reason)->toBe('Poor image quality')
        ->and($result->reviewed_by)->toBe($admin->id);

    Notification::assertSentTo($product->vendor->user, ProductRejectedNotification::class);
});

test('resubmitting a rejected product clears the rejection reason', function () {
    $product = Product::factory()->create(['status' => ProductStatus::Rejected, 'rejection_reason' => 'Bad photos']);

    $result = app(SubmitProductForApprovalAction::class)->handle($product);

    expect($result->status)->toBe(ProductStatus::PendingApproval)
        ->and($result->rejection_reason)->toBeNull();
});
