<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Cart\ApplyCouponAction;
use App\Actions\Cart\RemoveCouponAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Support\Cart\CartResolver;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class CartCouponController extends Controller
{
    public function store(ApplyCouponRequest $request, CartResolver $cartResolver, ApplyCouponAction $action): RedirectResponse
    {
        $cart = $cartResolver->resolve();

        try {
            $action->handle($cart, $request->string('code')->upper()->value());
        } catch (RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', 'coupon-applied');
    }

    public function destroy(CartResolver $cartResolver, RemoveCouponAction $action): RedirectResponse
    {
        $action->handle($cartResolver->resolve());

        return back()->with('status', 'coupon-removed');
    }
}
