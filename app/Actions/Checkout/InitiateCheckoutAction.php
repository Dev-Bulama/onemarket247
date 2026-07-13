<?php

namespace App\Actions\Checkout;

use App\Models\Cart;
use App\Models\CheckoutSession;
use Illuminate\Support\Str;

/**
 * Get-or-create the single live checkout session for a cart: reusing an
 * existing unresolved, unexpired session (rather than minting a fresh
 * idempotency key every time the checkout page loads) is what makes a
 * double form-submit or a reload of /checkout resolve to the same
 * eventual order instead of two.
 */
class InitiateCheckoutAction
{
    public function handle(Cart $cart): CheckoutSession
    {
        $existing = CheckoutSession::where('cart_id', $cart->id)
            ->whereNull('order_id')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            $existing->update([
                'subtotal' => $cart->subtotal(),
                'discount_amount' => $cart->discount(),
                'total' => $cart->total(),
            ]);

            return $existing->fresh();
        }

        return CheckoutSession::create([
            'cart_id' => $cart->id,
            'idempotency_key' => Str::random(40),
            'subtotal' => $cart->subtotal(),
            'discount_amount' => $cart->discount(),
            'total' => $cart->total(),
            'expires_at' => now()->addMinutes(30),
        ]);
    }
}
