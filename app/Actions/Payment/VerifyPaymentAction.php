<?php

namespace App\Actions\Payment;

use App\Actions\Inventory\DeductStockAction;
use App\Actions\Order\UpdateVendorOrderStatusAction;
use App\Actions\Wallet\CreditVendorWalletAction;
use App\Enums\PaymentLogDirection;
use App\Enums\PaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Services\Payment\PaystackGateway;
use Illuminate\Support\Facades\DB;

/**
 * A frontend "payment succeeded" redirect is never trusted alone — this
 * always calls Paystack's server-to-server verify API, whether triggered
 * by the browser callback or a webhook (see docs/architecture/
 * 09-lifecycles.md "Payment Lifecycle"). Row-locks the payment first so a
 * webhook and a browser callback racing to verify the same payment can
 * only ever apply the "paid" side effects once — the same
 * lock-then-short-circuit idempotency shape as Phase 11's
 * CompleteCheckoutAction.
 */
class VerifyPaymentAction
{
    public function __construct(
        private readonly PaystackGateway $gateway,
        private readonly UpdateVendorOrderStatusAction $updateVendorOrderStatus,
    ) {}

    public function handle(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment $payment */
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Failed], true)) {
                return $payment;
            }

            if ($payment->gateway_reference === null) {
                throw new PaymentGatewayException('Cannot verify a payment that was never initialized.');
            }

            PaymentLog::create([
                'payment_id' => $payment->id,
                'gateway' => PaystackGateway::CODE,
                'direction' => PaymentLogDirection::Request,
                'payload' => ['action' => 'verify', 'reference' => $payment->gateway_reference],
            ]);

            $result = $this->gateway->verify($payment->gateway_reference);

            PaymentLog::create([
                'payment_id' => $payment->id,
                'gateway' => PaystackGateway::CODE,
                'direction' => PaymentLogDirection::Response,
                'payload' => $result->raw,
            ]);

            // Amount is re-checked against our own record rather than
            // trusted from the gateway response — the mitigation for
            // payment/price manipulation named in the security doc.
            if ($result->successful && $result->amount === $payment->amount) {
                $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => $result->paidAt ?? now(), 'meta' => $result->raw]);
                $this->confirmVendorOrders($payment);
                $this->deductReservedStock($payment);
                $this->creditVendorWallets($payment);
            } else {
                $payment->update(['status' => PaymentStatus::Failed, 'failed_at' => now(), 'meta' => $result->raw]);
            }

            return $payment->fresh();
        });
    }

    private function confirmVendorOrders(Payment $payment): void
    {
        $payment->order->vendorOrders()
            ->where('status', VendorOrderStatus::PendingPayment)
            ->get()
            ->each(fn ($vendorOrder) => $this->updateVendorOrderStatus->handle($vendorOrder, VendorOrderStatus::Confirmed, 'Payment confirmed.'));
    }

    private function deductReservedStock(Payment $payment): void
    {
        $payment->order->vendorOrders
            ->flatMap(fn ($vendorOrder) => $vendorOrder->orderItems)
            ->each(function ($item) {
                if ($item->warehouse_id === null) {
                    return;
                }

                app(DeductStockAction::class)->handle($item->warehouse, $item->sellable(), $item->quantity, null, $item->vendorOrder);
            });
    }

    private function creditVendorWallets(Payment $payment): void
    {
        $payment->order->vendorOrders->each(fn ($vendorOrder) => app(CreditVendorWalletAction::class)->handle($vendorOrder));
    }
}
