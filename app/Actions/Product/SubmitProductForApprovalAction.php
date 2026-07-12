<?php

namespace App\Actions\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Setting;

/**
 * A vendor's "submit for review" action. Mirrors the vendor-application
 * auto/manual split from Phase 5 (App\Actions\Vendor\SubmitVendorApplicationAction):
 * checks the products.approval_mode setting and either publishes
 * immediately or parks the product in pending_approval for an admin.
 */
class SubmitProductForApprovalAction
{
    public function handle(Product $product): Product
    {
        if ($this->isAutoApproved()) {
            $product->update([
                'status' => ProductStatus::Published,
                'published_at' => now(),
                'rejection_reason' => null,
            ]);

            return $product->fresh();
        }

        $product->update([
            'status' => ProductStatus::PendingApproval,
            'rejection_reason' => null,
        ]);

        return $product->fresh();
    }

    private function isAutoApproved(): bool
    {
        return Setting::where('key', 'products.approval_mode')->first()?->typed_value === 'automatic';
    }
}
