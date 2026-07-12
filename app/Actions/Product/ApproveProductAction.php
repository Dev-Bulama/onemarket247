<?php

namespace App\Actions\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;

class ApproveProductAction
{
    public function handle(Product $product, ?User $reviewer = null): Product
    {
        $product->update([
            'status' => ProductStatus::Published,
            'published_at' => $product->published_at ?? now(),
            'rejection_reason' => null,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);

        return $product->fresh();
    }
}
