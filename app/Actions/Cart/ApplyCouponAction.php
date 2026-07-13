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

        $subtotal = $cart->subtotal();

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
