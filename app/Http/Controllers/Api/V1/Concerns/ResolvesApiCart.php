<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Cart;
use App\Support\Cart\CartResolver;
use Illuminate\Http\Request;

/**
 * Shared by every Api\V1 controller that needs "the cart for this
 * request" — the Sanctum-authenticated user's cart, or a guest cart
 * identified by the `cart_token` the mobile client persisted itself (see
 * App\Support\Cart\CartResolver's docblock for why the API can't use the
 * web's cookie mechanism).
 */
trait ResolvesApiCart
{
    private function resolveApiCart(Request $request, CartResolver $cartResolver): Cart
    {
        $user = $request->user('sanctum');
        $guestToken = $user ? null : $request->string('cart_token')->value();

        $cart = $cartResolver->resolve($user, $guestToken);

        return $cart->loadMissing($this->cartEagerLoads());
    }

    /**
     * @return array<int, string>
     */
    private function cartEagerLoads(): array
    {
        return [
            'activeItems.product.vendor.store',
            'activeItems.variation.attributeValues.attribute',
            'savedItems.product.vendor.store',
            'savedItems.variation.attributeValues.attribute',
            'coupon',
        ];
    }
}
