<?php

use App\Enums\ReviewStatus;
use App\Enums\UserType;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Customers\RelationManagers\ProductReviewsRelationManager;
use App\Filament\Resources\ProductQuestions\Pages\ViewProductQuestion;
use App\Filament\Resources\ProductQuestions\RelationManagers\AnswersRelationManager;
use App\Filament\Resources\ProductReviews\Pages\ViewProductReview;
use App\Models\Address;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductReview;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function superAdminForEngagement(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('a super admin can load the review and question index and view pages', function () {
    $admin = superAdminForEngagement();
    $review = ProductReview::factory()->create();
    $question = ProductQuestion::factory()->create();

    $this->actingAs($admin, 'admin')->get('/admin/product-reviews')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/product-reviews/{$review->id}")->assertOk();
    $this->actingAs($admin, 'admin')->get('/admin/product-questions')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/product-questions/{$question->id}")->assertOk();
});

test('a super admin can approve and reject a review from its view page', function () {
    $admin = superAdminForEngagement();
    $pending = ProductReview::factory()->create();
    $rejected = ProductReview::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ViewProductReview::class, ['record' => $pending->getRouteKey()])
        ->callAction('approve');

    expect($pending->fresh()->status)->toBe(ReviewStatus::Approved);

    Livewire::actingAs($admin, 'admin')
        ->test(ViewProductReview::class, ['record' => $rejected->getRouteKey()])
        ->callAction('reject', data: ['reason' => 'Contains spam.']);

    expect($rejected->fresh())
        ->status->toBe(ReviewStatus::Rejected)
        ->rejection_reason->toBe('Contains spam.');
});

test('a super admin can answer a question through the relation manager, marking it answered', function () {
    $admin = superAdminForEngagement();
    $question = ProductQuestion::factory()->create(['is_answered' => false]);

    Livewire::actingAs($admin, 'admin')
        ->test(AnswersRelationManager::class, [
            'ownerRecord' => $question,
            'pageClass' => ViewProductQuestion::class,
        ])
        ->callTableAction('create', data: ['answer' => 'Yes, it ships worldwide.']);

    expect($question->fresh()->is_answered)->toBeTrue()
        ->and($question->fresh()->answers()->first()->answer)->toBe('Yes, it ships worldwide.');
});

test('the customer resource exposes read-only addresses and reviews relation managers', function () {
    $admin = superAdminForEngagement();
    $customer = User::factory()->create(['user_type' => UserType::Customer]);
    $address = Address::factory()->create(['addressable_id' => $customer->id, 'addressable_type' => User::class]);
    $product = Product::factory()->create();
    $review = ProductReview::factory()->for($product)->create(['customer_id' => $customer->id]);

    Livewire::actingAs($admin, 'admin')
        ->test(AddressesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
        ->assertCanSeeTableRecords([$address]);

    Livewire::actingAs($admin, 'admin')
        ->test(ProductReviewsRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
        ->assertCanSeeTableRecords([$review]);
});
