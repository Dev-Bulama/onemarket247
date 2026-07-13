<?php

namespace App\Listeners;

use App\Actions\Cart\MergeGuestCartIntoCustomerCartAction;
use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\User;
use App\Support\Cart\CartResolver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;

/**
 * Fires for every "web" guard login (password, registration, social,
 * two-factor challenge — they all funnel through Auth::guard('web')->login()
 * and therefore this one event), so cart merging doesn't need to be
 * duplicated across every login entry point.
 */
class MergeGuestCartOnLogin
{
    public function __construct(private readonly MergeGuestCartIntoCustomerCartAction $action) {}

    public function handle(Login $event): void
    {
        if ($event->guard !== 'web' || ! $event->user instanceof User) {
            return;
        }

        $token = Request::cookie(CartResolver::COOKIE_NAME);

        if (! $token) {
            return;
        }

        $guestCart = Cart::where('session_token', $token)->where('status', CartStatus::Active)->first();

        if (! $guestCart) {
            return;
        }

        $this->action->handle($guestCart, $event->user);

        Cookie::queue(Cookie::forget(CartResolver::COOKIE_NAME));
    }
}
