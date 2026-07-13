<?php

namespace App\Actions\Cart;

use App\Models\Cart;

class RemoveCouponAction
{
    public function handle(Cart $cart): void
    {
        $cart->coupon()->delete();
    }
}
