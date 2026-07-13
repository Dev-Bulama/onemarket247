<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartCoupon;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartCoupon>
 */
class CartCouponFactory extends Factory
{
    protected $model = CartCoupon::class;

    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'coupon_id' => Coupon::factory(),
            'code' => fn (array $attributes) => Coupon::find($attributes['coupon_id'])?->code ?? 'SAVE-0000',
            'discount_amount' => 0,
        ];
    }
}
