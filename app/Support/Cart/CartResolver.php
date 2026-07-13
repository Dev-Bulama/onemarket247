<?php

namespace App\Support\Cart;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Resolves "the cart for this request": the authenticated customer's
 * active cart, or a guest cart identified by a random token in a signed
 * cookie. The cookie token — not the session id — is the guest cart's
 * identity, since the session can rotate (e.g. on login) independently of
 * the cart the guest has been building.
 *
 * Reads the incoming cookie via the Request facade rather than a
 * constructor-injected Illuminate\Http\Request: this class is itself
 * injected into controller constructors, which are instantiated before
 * Laravel's EncryptCookies middleware has necessarily finished decorating
 * the bound 'request' instance seen at that exact resolution moment. The
 * facade re-resolves 'request' from the container on every call, so it
 * always sees the fully-processed request by the time resolve() runs.
 */
class CartResolver
{
    public const COOKIE_NAME = 'cart_token';

    private const COOKIE_MINUTES = 60 * 24 * 30;

    public function resolve(): Cart
    {
        $user = Auth::guard('web')->user();

        return $user instanceof User
            ? $this->resolveForCustomer($user)
            : $this->resolveForGuest();
    }

    /**
     * Read-only lookup for places like the nav cart badge, which render on
     * every storefront page view and must never have the side effect of
     * creating a cart (and setting a cookie) for a visitor who has never
     * added anything.
     */
    public function peek(): ?Cart
    {
        $user = Auth::guard('web')->user();

        if ($user instanceof User) {
            return $user->carts()->where('status', CartStatus::Active)->first();
        }

        $token = Request::cookie(self::COOKIE_NAME);

        return $token
            ? Cart::where('session_token', $token)->where('status', CartStatus::Active)->first()
            : null;
    }

    private function resolveForCustomer(User $user): Cart
    {
        return $user->carts()->where('status', CartStatus::Active)->first()
            ?? $user->carts()->create(['status' => CartStatus::Active]);
    }

    private function resolveForGuest(): Cart
    {
        $token = Request::cookie(self::COOKIE_NAME);

        if ($token) {
            $cart = Cart::where('session_token', $token)->where('status', CartStatus::Active)->first();

            if ($cart) {
                return $cart;
            }
        }

        $token = Str::random(64);

        $cart = Cart::create(['session_token' => $token, 'status' => CartStatus::Active]);

        Cookie::queue(self::COOKIE_NAME, $token, self::COOKIE_MINUTES);

        return $cart;
    }
}
