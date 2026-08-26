<?php

namespace App\Actions\Payment;

use App\Actions\Inventory\DeductStockAction;
use App\Actions\Order\UpdateVendorOrderStatusAction;
use App\Actions\Wallet\CreditVendorWalletAction;
use App\Enums\PaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Payment;
use Illuminate\Support\Carbon;

/**
 * The side effects a successful payment always triggers, however it was
 * confirmed — a verified Paystack transaction (VerifyPaymentAction) or an
 * admin manually confirming a bank transfer (ConfirmBankTransferPaymentAction).
 * Callers are responsible for their own row lock + transaction + idempotency
 * check before calling this; it assumes $payment is already locked and not
 * already Paid/Failed.
 */
class MarkPaymentPaidAction
{
    public function __construct(private readonly UpdateVendorOrderStatusAction $updateVendorOrderStatus) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function handle(Payment $payment, ?Carbon $paidAt = null, array $meta = []): Payment
    {
        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => $paidAt ?? now(),
            'meta' => $meta ?: $payment->meta,
        ]);

        $this->confirmVendorOrders($payment);
        $this->deductReservedStock($payment);
        $this->creditVendorWallets($payment);

        return $payment->fresh();
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
