<?php

namespace App\Actions\Payment;

use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A bank transfer can't be verified server-to-server like Paystack — an
 * admin/finance staffer confirms it manually (see PaymentsTable's
 * "Confirm bank transfer" action) after checking the account for the
 * matching deposit. Applies the exact same "payment succeeded" side
 * effects as a verified Paystack payment via MarkPaymentPaidAction, so
 * the two payment methods can never drift in what happens on success.
 */
class ConfirmBankTransferPaymentAction
{
    public function __construct(private readonly MarkPaymentPaidAction $markPaymentPaid) {}

    public function handle(Payment $payment, ?User $confirmedBy = null): Payment
    {
        return DB::transaction(function () use ($payment, $confirmedBy) {
            /** @var Payment $payment */
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->gateway !== 'bank_transfer') {
                throw new PaymentGatewayException('This payment was not made by bank transfer.');
            }

            if ($payment->status === PaymentStatus::Paid) {
                return $payment;
            }

            if ($payment->status !== PaymentStatus::Pending) {
                throw new PaymentGatewayException("Cannot confirm a payment that is currently \"{$payment->status->getLabel()}\".");
            }

            return $this->markPaymentPaid->handle($payment, now(), [
                'confirmed_by' => $confirmedBy?->id,
                'confirmed_manually' => true,
            ]);
        });
    }
}
