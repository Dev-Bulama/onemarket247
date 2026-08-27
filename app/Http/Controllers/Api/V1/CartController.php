<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cart\MergeGuestCartIntoCustomerCartAction;
use App\Enums\CartStatus;
use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCart;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CartResource;
use App\Models\Cart;
use App\Support\Api\ApiResponse;
use App\Support\Cart\CartResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ResolvesApiCart;

    public function index(Request $request, CartResolver $cartResolver): JsonResponse
    {
        return ApiResponse::success(new CartResource($this->resolveApiCart($request, $cartResolver)));
    }

    /**
     * A bearer-token client has no cookie jar for Laravel's guest-cart
     * cookie, so it persists the guest cart's own token itself (returned
     * as `guest_token` on every cart response) and calls this once it has
     * an account/token, so the two carts are folded into one rather than
     * silently losing whatever the guest already added — see
     * MergeGuestCartOnLogin, which does the equivalent for the web login
     * flow (a flow the API's token-based login never triggers).
     */
    public function merge(Request $request, CartResolver $cartResolver, MergeGuestCartIntoCustomerCartAction $action): JsonResponse
    {
        $user = $request->user('sanctum');

        abort_unless($user, 401);

        $data = $request->validate(['guest_token' => ['required', 'string']]);

        $guestCart = Cart::where('session_token', $data['guest_token'])->where('status', CartStatus::Active)->first();

        $cart = $guestCart
            ? $action->handle($guestCart, $user)
            : $cartResolver->resolve($user);

        return ApiResponse::success(new CartResource($cart->fresh($this->cartEagerLoads())));
    }
}
