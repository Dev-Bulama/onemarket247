<?php

namespace App\Actions\Payment;

use App\Enums\PaymentLogDirection;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Services\Payment\PaystackGateway;

/**
 * Amount always comes from the order/payment row, never client input (see
 * docs/architecture/10-security-architecture.md "Payment Security" —
 * "Amounts are always recomputed server-side ... never accepted from
 * client input"). A payment attempt that already has a gateway_reference
 * can't be re-initialized at the same reference (Paystack rejects
 * duplicate references), so a retry always gets a fresh Payment row —
 * matching docs/architecture/09-lifecycles.md's "customer can retry with a
 * fresh payment attempt (new payments row, same order)".
 */
class InitializePaymentAction
{
    public function __construct(private readonly PaystackGateway $gateway) {}

    /**
     * @return array{payment: Payment, authorization_url: string}
     */
    public function handle(Order $order, string $callbackUrl): array
    {
        $latest = $order->payments()->latest('id')->first();

        if ($latest?->status === PaymentStatus::Paid) {
            throw new PaymentGatewayException('This order has already been paid.');
        }

        $payment = ($latest && $latest->status === PaymentStatus::Pending && $latest->gateway_reference === null)
            ? $latest
            : $order->payments()->create(['status' => PaymentStatus::Pending, 'amount' => $order->total]);

        PaymentLog::create([
            'payment_id' => $payment->id,
            'gateway' => PaystackGateway::CODE,
            'direction' => PaymentLogDirection::Request,
            'payload' => ['reference' => $payment->reference, 'amount' => $payment->amount, 'callback_url' => $callbackUrl],
        ]);

        try {
            $result = $this->gateway->initialize($payment, $callbackUrl);
        } catch (PaymentGatewayException $e) {
            PaymentLog::create([
                'payment_id' => $payment->id,
                'gateway' => PaystackGateway::CODE,
                'direction' => PaymentLogDirection::Error,
                'payload' => ['message' => $e->getMessage()],
            ]);

            throw $e;
        }

        PaymentLog::create([
            'payment_id' => $payment->id,
            'gateway' => PaystackGateway::CODE,
            'direction' => PaymentLogDirection::Response,
            'payload' => $result->raw,
        ]);

        $payment->update([
            'gateway' => PaystackGateway::CODE,
            'gateway_reference' => $result->reference,
            'status' => PaymentStatus::Processing,
        ]);

        return ['payment' => $payment->fresh(), 'authorization_url' => $result->authorizationUrl];
    }
}
