<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cart\ApplyCouponAction;
use App\Actions\Cart\RemoveCouponAction;
use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Support\Api\ApiResponse;
use App\Support\Cart\CartResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CartCouponController extends Controller
{
    use ResolvesApiCart;

    public function store(ApplyCouponRequest $request, CartResolver $cartResolver, ApplyCouponAction $action): JsonResponse
    {
        $cart = $this->resolveApiCart($request, $cartResolver);

        try {
            $action->handle($cart, $request->string('code')->upper()->value());
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), ['code' => [$e->getMessage()]], 'COUPON_ERROR');
        }

        return ApiResponse::success(new CartResource($cart->fresh($this->cartEagerLoads())));
    }

    public function destroy(Request $request, CartResolver $cartResolver, RemoveCouponAction $action): JsonResponse
    {
        $cart = $this->resolveApiCart($request, $cartResolver);

        $action->handle($cart);

        return ApiResponse::success(new CartResource($cart->fresh($this->cartEagerLoads())));
    }
}
