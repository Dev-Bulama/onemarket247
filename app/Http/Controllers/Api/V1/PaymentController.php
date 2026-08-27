<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payment\InitializePaymentAction;
use App\Actions\Payment\VerifyPaymentAction;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    /**
     * A mobile Paystack SDK integration opens `authorization_url` in an
     * in-app browser/webview itself rather than following a server
     * redirect — this returns the URL as data instead of
     * Storefront\PaymentController's redirect()->away().
     */
    public function initialize(Request $request, Order $order, InitializePaymentAction $action): JsonResponse
    {
        Gate::forUser($request->user('sanctum'))->authorize('view', $order);

        $result = $action->handle($order, $request->string('callback_url')->value() ?: url('/'));

        return ApiResponse::success([
            'authorization_url' => $result['authorization_url'],
            'reference' => $result['payment']->reference,
        ]);
    }

    /**
     * Called by the mobile app once its Paystack SDK reports the in-app
     * payment finished — same server-to-server verify Storefront\PaymentController's
     * browser callback triggers; a client-reported "success" is never
     * trusted on its own (see docs/architecture/10-security-architecture.md).
     */
    public function verify(Request $request, Order $order, VerifyPaymentAction $action): JsonResponse
    {
        Gate::forUser($request->user('sanctum'))->authorize('view', $order);

        $payment = $order->payments()->latest('id')->first();

        if (! $payment || $payment->gateway_reference === null) {
            return ApiResponse::error('No payment attempt to verify for this order.', [], 'NO_PAYMENT_ATTEMPT', 404);
        }

        $payment = $action->handle($payment);

        return ApiResponse::success([
            'status' => $payment->status->value,
            'paid_at' => $payment->paid_at,
        ]);
    }
}
