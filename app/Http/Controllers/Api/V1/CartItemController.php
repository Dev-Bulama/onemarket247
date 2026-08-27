<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cart\AddCartItemAction;
use App\Actions\Cart\UpdateCartItemQuantityAction;
use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddCartItemRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Support\Api\ApiResponse;
use App\Support\Cart\CartResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CartItemController extends Controller
{
    use ResolvesApiCart;

    public function store(AddCartItemRequest $request, CartResolver $cartResolver, AddCartItemAction $action): JsonResponse
    {
        $cart = $this->resolveApiCart($request, $cartResolver);
        $product = Product::findOrFail($request->integer('product_id'));
        $variation = $request->filled('product_variation_id')
            ? ProductVariation::findOrFail($request->integer('product_variation_id'))
            : null;

        try {
            $action->handle($cart, $product, $variation, $request->integer('quantity'));
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), ['quantity' => [$e->getMessage()]], 'CART_ERROR');
        }

        return ApiResponse::success(new CartResource($cart->fresh($this->cartEagerLoads())), status: 201);
    }

    public function update(UpdateCartItemRequest $request, CartResolver $cartResolver, CartItem $cartItem, UpdateCartItemQuantityAction $action): JsonResponse
    {
        $cart = $this->assertOwnsItem($request, $cartResolver, $cartItem);

        try {
            $action->handle($cartItem, $request->integer('quantity'));
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), ['quantity' => [$e->getMessage()]], 'CART_ERROR');
        }

        return ApiResponse::success(new CartResource($cart->fresh($this->cartEagerLoads())));
    }

    public function destroy(Request $request, CartResolver $cartResolver, CartItem $cartItem): JsonResponse
    {
        $cart = $this->assertOwnsItem($request, $cartResolver, $cartItem);

        $cartItem->delete();

        return ApiResponse::success(new CartResource($cart->fresh($this->cartEagerLoads())));
    }

    private function assertOwnsItem(Request $request, CartResolver $cartResolver, CartItem $cartItem): Cart
    {
        $cart = $this->resolveApiCart($request, $cartResolver);

        abort_unless($cartItem->cart_id === $cart->id, 403);

        return $cart;
    }
}
