<?php

namespace App\Policies;

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\ProductQuestion;
use App\Models\User;

/**
 * Mirrors ProductReviewPolicy: any customer can ask a question; a vendor
 * (or staff with store.questions.answer) can answer questions on their own
 * store's products; an admin with questions.manage can do anything,
 * independent of ownership.
 */
class ProductQuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('questions.manage') || $user->storeStaff()->exists() || $user->vendor !== null;
    }

    public function view(User $user, ProductQuestion $question): bool
    {
        return $question->customer_id === $user->id
            || $this->hasStoreQuestionAccess($user, $question)
            || $user->can('questions.manage');
    }

    public function create(User $user): bool
    {
        return $user->user_type === UserType::Customer;
    }

    public function delete(User $user, ProductQuestion $question): bool
    {
        return $question->customer_id === $user->id || $user->can('questions.manage');
    }

    public function answer(User $user, ProductQuestion $question): bool
    {
        return $this->hasStoreQuestionAccess($user, $question) || $user->can('questions.manage');
    }

    private function hasStoreQuestionAccess(User $user, ProductQuestion $question): bool
    {
        $vendorId = $question->product->vendor_id;

        if ($question->product->vendor->user_id === $user->id) {
            return true;
        }

        $isActiveStaff = $user->storeStaff()
            ->whereHas('store', fn ($query) => $query->where('vendor_id', $vendorId))
            ->where('status', StoreStaffStatus::Active)
            ->exists();

        return $isActiveStaff && $user->can('store.questions.answer');
    }
}
