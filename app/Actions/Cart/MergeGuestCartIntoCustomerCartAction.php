<?php

namespace App\Actions\Cart;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\User;

/**
 * Runs on login (see App\Listeners\MergeGuestCartOnLogin). Matching lines
 * (same product + variation) have their quantities summed and price
 * refreshed; the customer's own saved-for-later state always wins over the
 * guest cart's for a line that exists in both. A coupon already applied to
 * the customer's cart is never overwritten by the guest cart's.
 */
class MergeGuestCartIntoCustomerCartAction
{
    public function handle(Cart $guestCart, User $customer): Cart
    {
        $customerCart = $customer->carts()->where('status', CartStatus::Active)->first()
            ?? $customer->carts()->create(['status' => CartStatus::Active]);

        foreach ($guestCart->items as $guestItem) {
            $existing = $customerCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('product_variation_id', $guestItem->product_variation_id)
                ->first();

            if ($existing) {
                $existing->update([
                    'quantity' => $existing->quantity + $guestItem->quantity,
                    'unit_price' => $existing->currentUnitPrice() ?? $existing->unit_price,
                ]);

                continue;
            }

            $customerCart->items()->create([
                'product_id' => $guestItem->product_id,
                'product_variation_id' => $guestItem->product_variation_id,
                'quantity' => $guestItem->quantity,
                'unit_price' => $guestItem->unit_price,
                'saved_for_later' => $guestItem->saved_for_later,
            ]);
        }

        if (! $customerCart->coupon && $guestCart->coupon) {
            $customerCart->coupon()->create([
                'coupon_id' => $guestCart->coupon->coupon_id,
                'code' => $guestCart->coupon->code,
                'discount_amount' => $guestCart->coupon->discount_amount,
            ]);
        }

        $guestCart->items()->delete();
        $guestCart->coupon()->delete();
        $guestCart->update(['status' => CartStatus::Merged]);

        return $customerCart->fresh();
    }
}
