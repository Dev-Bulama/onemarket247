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
 * active cart, or a guest cart identified by a random token. On the web,
 * that token lives in a signed cookie — the cookie token, not the session
 * id, is the guest cart's identity, since the session can rotate (e.g. on
 * login) independently of the cart the guest has been building.
 *
 * The mobile API (Api\V1\CartController) calls resolve()/peek() with an
 * explicit $user (the Sanctum-authenticated user, which lives on a
 * different guard than the web session) and/or $guestToken (a value the
 * mobile app persists itself, since a bearer-token client has no cookie
 * jar Laravel can rely on) — a transport difference, not a different cart
 * concept. When $guestToken is passed explicitly, a freshly created
 * guest cart's token is never queued as a cookie, since there's no web
 * response to carry it; the caller is expected to return $cart->session_token
 * to the mobile client instead.
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

    public function resolve(?User $user = null, ?string $guestToken = null): Cart
    {
        $user ??= Auth::guard('web')->user();

        return $user instanceof User
            ? $this->resolveForCustomer($user)
            : $this->resolveForGuest($guestToken);
    }

    /**
     * Read-only lookup for places like the nav cart badge, which render on
     * every storefront page view and must never have the side effect of
     * creating a cart (and setting a cookie) for a visitor who has never
     * added anything.
     */
    public function peek(?User $user = null, ?string $guestToken = null): ?Cart
    {
        $user ??= Auth::guard('web')->user();

        if ($user instanceof User) {
            return $user->carts()->where('status', CartStatus::Active)->first();
        }

        $token = $guestToken ?? Request::cookie(self::COOKIE_NAME);

        return $token
            ? Cart::where('session_token', $token)->where('status', CartStatus::Active)->first()
            : null;
    }

    private function resolveForCustomer(User $user): Cart
    {
        return $user->carts()->where('status', CartStatus::Active)->first()
            ?? $user->carts()->create(['status' => CartStatus::Active]);
    }

    private function resolveForGuest(?string $suppliedToken): Cart
    {
        $usingCookie = $suppliedToken === null;
        $token = $suppliedToken ?? Request::cookie(self::COOKIE_NAME);

        if ($token) {
            $cart = Cart::where('session_token', $token)->where('status', CartStatus::Active)->first();

            if ($cart) {
                return $cart;
            }
        }

        $token = Str::random(64);

        $cart = Cart::create(['session_token' => $token, 'status' => CartStatus::Active]);

        if ($usingCookie) {
            Cookie::queue(self::COOKIE_NAME, $token, self::COOKIE_MINUTES);
        }

        return $cart;
    }
}
