<?php

namespace App\Actions\Payment;

use App\Enums\PaymentLogDirection;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\User;
use App\Services\Payment\PaystackGateway;
use Illuminate\Support\Facades\DB;

/**
 * Admin-initiated refund of an already-paid payment (see
 * docs/architecture/09-lifecycles.md "Payment Lifecycle" — refunded /
 * partially_refunded via Refund records). This is deliberately scoped to
 * "refunds update financial records" (this phase's own gate), not the
 * customer-facing return-request workflow — that needs Phase 18's
 * return_requests/return_evidence tables and doesn't exist yet.
 * refunded_amount lives directly on payments rather than a dedicated
 * refunds table for the same reason Phase 11 kept payments minimal:
 * nothing yet needs more than "how much of this payment has been given
 * back."
 */
class RefundPaymentAction
{
    public function __construct(private readonly PaystackGateway $gateway) {}

    public function handle(Payment $payment, int $amount, ?User $actor = null): Payment
    {
        if (! $payment->isRefundable()) {
            throw new PaymentGatewayException('Only a paid (or partially refunded) payment can be refunded.');
        }

        $refundable = $payment->amount - $payment->refunded_amount;

        if ($amount <= 0 || $amount > $refundable) {
            throw new PaymentGatewayException("Refund amount must be between 1 and {$refundable}.");
        }

        PaymentLog::create([
            'payment_id' => $payment->id,
            'gateway' => PaystackGateway::CODE,
            'direction' => PaymentLogDirection::Request,
            'payload' => ['action' => 'refund', 'reference' => $payment->gateway_reference, 'amount' => $amount, 'actor_id' => $actor?->id],
        ]);

        $result = $this->gateway->refund($payment->gateway_reference, $amount);

        PaymentLog::create([
            'payment_id' => $payment->id,
            'gateway' => PaystackGateway::CODE,
            'direction' => PaymentLogDirection::Response,
            'payload' => $result->raw,
        ]);

        return DB::transaction(function () use ($payment, $amount) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $refundedAmount = $payment->refunded_amount + $amount;

            $payment->update([
                'refunded_amount' => $refundedAmount,
                'status' => $refundedAmount >= $payment->amount ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded,
            ]);

            return $payment->fresh();
        });
    }
}
