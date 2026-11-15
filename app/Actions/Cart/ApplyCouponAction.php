<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartCoupon;
use App\Models\Coupon;
use RuntimeException;

class ApplyCouponAction
{
    public function handle(Cart $cart, string $code): CartCoupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isValidNow()) {
            throw new RuntimeException('This coupon code is invalid or has expired.');
        }

        // A vendor's own coupon only ever discounts that vendor's share of
        // the cart — both the minimum-spend check and the discount itself
        // are computed against their items alone, never the whole cart.
        $subtotal = $coupon->isVendorCoupon()
            ? $cart->activeItems->filter(fn ($item) => $item->product?->vendor_id === $coupon->vendor_id)
                ->sum(fn ($item) => $item->unit_price * $item->quantity)
            : $cart->subtotal();

        if ($coupon->isVendorCoupon() && $subtotal === 0) {
            throw new RuntimeException('This coupon only applies to items from that vendor — add one to your cart first.');
        }

        if ($coupon->minimum_spend !== null && $subtotal < $coupon->minimum_spend) {
            $minimum = number_format($coupon->minimum_spend / 100, 2);
            throw new RuntimeException("This coupon requires a minimum spend of \${$minimum}.");
        }

        $cart->coupon()->delete();

        return $cart->coupon()->create([
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'discount_amount' => $coupon->discountFor($subtotal),
        ]);
    }
}
