<?php

namespace App\Actions\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductRejectedNotification;

class RejectProductAction
{
    public function handle(Product $product, string $reason, ?User $reviewer = null): Product
    {
        $product->update([
            'status' => ProductStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);

        $product->vendor->user->notify(new ProductRejectedNotification($product));

        return $product->fresh();
    }
}
