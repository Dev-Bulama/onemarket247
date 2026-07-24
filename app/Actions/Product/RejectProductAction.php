<?php

namespace App\Actions\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductRejectedNotification;
use Throwable;

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

        // The rejection already persisted above — a mail transport failure
        // here must not turn a successful moderation action into a 500.
        try {
            $product->vendor->user->notify(new ProductRejectedNotification($product));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $product->fresh();
    }
}
