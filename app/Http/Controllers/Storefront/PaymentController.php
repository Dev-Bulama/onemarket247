<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Payment\InitializePaymentAction;
use App\Actions\Payment\VerifyPaymentAction;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function initialize(Order $order, InitializePaymentAction $action): RedirectResponse
    {
        Gate::authorize('view', $order);

        try {
            $result = $action->handle($order, route('checkout.payment.callback', $order));
        } catch (PaymentGatewayException $e) {
            return redirect()->route('checkout.confirmation', $order)->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()->away($result['authorization_url']);
    }

    /**
     * Paystack redirects the browser here after the hosted payment page —
     * a frontend redirect is never sufficient to mark an order paid (see
     * docs/architecture/10-security-architecture.md "Payment Security"),
     * so this only triggers a fresh server-to-server verify. The webhook
     * is the authoritative path if this call races or fails; VerifyPaymentAction
     * is idempotent either way.
     */
    public function callback(Order $order): RedirectResponse
    {
        Gate::authorize('view', $order);

        $payment = $order->payments()->latest('id')->first();

        if ($payment && $payment->gateway_reference !== null) {
            try {
                app(VerifyPaymentAction::class)->handle($payment);
            } catch (PaymentGatewayException) {
                // Left pending — the webhook (or a later retry) will catch it up.
            }
        }

        return redirect()->route('checkout.confirmation', $order);
    }
}
