<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Checkout\CompleteCheckoutAction;
use App\Actions\Checkout\InitiateCheckoutAction;
use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Http\Resources\Api\V1\CheckoutSessionResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\CheckoutSession;
use App\Support\Api\ApiResponse;
use App\Support\Cart\CartResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    use ResolvesApiCart;

    public function init(Request $request, CartResolver $cartResolver, InitiateCheckoutAction $action): JsonResponse
    {
        $cart = $this->resolveApiCart($request, $cartResolver);

        if ($cart->activeItems->isEmpty()) {
            return ApiResponse::error('Your cart is empty.', [], 'CART_EMPTY');
        }

        $session = $action->handle($cart);

        return ApiResponse::success(new CheckoutSessionResource($session));
    }

    public function complete(CheckoutRequest $request, CartResolver $cartResolver, CompleteCheckoutAction $action): JsonResponse
    {
        $cart = $this->resolveApiCart($request, $cartResolver);
        $user = $request->user('sanctum');

        $session = CheckoutSession::where('idempotency_key', $request->string('checkout_session_key')->value())
            ->where('cart_id', $cart->id)
            ->first();

        if (! $session) {
            return ApiResponse::error('Your checkout session could not be found. Please start checkout again.', [], 'CHECKOUT_SESSION_NOT_FOUND', 404);
        }

        $shippingData = [
            'customer_id' => $user?->id,
            'guest_name' => $user ? null : $request->string('full_name')->value(),
            'guest_email' => $user ? null : $request->string('email')->value(),
            'guest_phone' => $user ? null : ($request->string('phone')->value() ?: null),
            'shipping_full_name' => $request->string('full_name')->value(),
            'shipping_phone' => $request->string('phone')->value() ?: null,
            'shipping_address_line_1' => $request->string('address_line_1')->value(),
            'shipping_address_line_2' => $request->string('address_line_2')->value() ?: null,
            'shipping_country_id' => $request->integer('country_id'),
            'shipping_state_id' => $request->filled('state_id') ? $request->integer('state_id') : null,
            'shipping_city_id' => $request->filled('city_id') ? $request->integer('city_id') : null,
            'shipping_postal_code' => $request->string('postal_code')->value() ?: null,
            'payment_method' => $request->string('payment_method')->value() ?: null,
        ];

        $order = $action->handle($session, $shippingData);
        $order->load(['vendorOrders.orderItems', 'payments', 'shippingCountry', 'shippingState', 'shippingCity']);

        return ApiResponse::success(new OrderResource($order), status: 201);
    }

    public function status(string $checkoutSessionKey): JsonResponse
    {
        $session = CheckoutSession::where('idempotency_key', $checkoutSessionKey)
            ->with('order')
            ->firstOrFail();

        return ApiResponse::success(new CheckoutSessionResource($session));
    }
}
