<?php

namespace App\Actions\Checkout;

use App\Actions\Cart\Concerns\ChecksAvailability;
use App\Actions\Commission\RecordOrderItemCommissionAction;
use App\Actions\Inventory\ReserveStockAction;
use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\CheckoutValidationException;
use App\Exceptions\InsufficientStockException;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\WarehouseStock;
use App\Services\Order\OrderStatusAggregator;
use Illuminate\Support\Facades\DB;

/**
 * The checkout → order-creation flow from
 * docs/architecture/09-lifecycles.md "Order Lifecycle": locks the checkout
 * session row first so a double form-submit (or a replayed request with
 * the same idempotency key) can never create two orders, re-validates
 * every cart line's live price and stock before committing to anything,
 * reserves stock (never deducts — deduction happens on payment success,
 * which is Phase 13's job), and splits items into one VendorOrder per
 * vendor represented in the cart.
 */
class CompleteCheckoutAction
{
    use ChecksAvailability;

    public function __construct(private readonly OrderStatusAggregator $aggregator) {}

    public function handle(CheckoutSession $session, array $shippingData): Order
    {
        return DB::transaction(function () use ($session, $shippingData) {
            $lockedSession = CheckoutSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($lockedSession->order_id !== null) {
                return $lockedSession->order;
            }

            if ($lockedSession->isExpired()) {
                throw new CheckoutValidationException('This checkout session has expired. Please try again.');
            }

            $cart = $lockedSession->cart;
            $items = $cart->activeItems()->with(['product', 'variation'])->get();

            if ($items->isEmpty()) {
                throw new CheckoutValidationException('Your cart is empty.');
            }

            foreach ($items as $item) {
                $sellable = $item->variation ?? $item->product;
                $currentPrice = $item->currentUnitPrice();

                if ($currentPrice === null || $currentPrice !== $item->unit_price) {
                    throw new CheckoutValidationException('Prices have changed since you added items to your cart. Please review your cart before checking out.');
                }

                $this->assertAvailable($sellable, $item->quantity);
            }

            $currency = Currency::where('is_default', true)->first() ?? Currency::first();

            if (! $currency) {
                throw new CheckoutValidationException('No currency is configured for this store yet.');
            }

            $order = Order::create([
                ...$shippingData,
                'currency_id' => $currency->id,
                'subtotal' => $cart->subtotal(),
                'discount_amount' => $cart->discount(),
                'shipping_amount' => 0,
                'tax_amount' => 0,
                'total' => $cart->total(),
                'coupon_code' => $cart->coupon?->code,
                'status' => OrderStatus::PendingPayment,
                'placed_at' => now(),
            ]);

            $itemsByVendor = $items->groupBy(fn (CartItem $item) => $item->product->vendor_id);

            foreach ($itemsByVendor as $vendorId => $vendorItems) {
                $vendorSubtotal = $vendorItems->sum(fn (CartItem $item) => $item->lineTotal());

                $vendorOrder = $order->vendorOrders()->create([
                    'vendor_id' => $vendorId,
                    'subtotal' => $vendorSubtotal,
                    // The coupon discount is applied only at the parent-order
                    // level this phase; proportional per-vendor attribution
                    // only matters once commission calculation (Phase 14)
                    // needs it, so vendor_order totals are deliberately
                    // gross (pre-discount) for now.
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'tax_amount' => 0,
                    'total' => $vendorSubtotal,
                    'status' => VendorOrderStatus::PendingPayment,
                ]);

                foreach ($vendorItems as $item) {
                    $sellable = $item->variation ?? $item->product;
                    $stock = $sellable->manage_stock ? $this->selectWarehouseStock($sellable, $item->quantity) : null;

                    $orderItem = $vendorOrder->orderItems()->create([
                        'product_id' => $item->product_id,
                        'product_variation_id' => $item->product_variation_id,
                        'warehouse_id' => $stock?->warehouse_id,
                        'product_name' => $item->product->name,
                        'sku' => $item->variation->sku ?? $item->product->sku,
                        'unit_price' => $item->unit_price,
                        'quantity' => $item->quantity,
                        'line_total' => $item->lineTotal(),
                    ]);

                    app(RecordOrderItemCommissionAction::class)->handle($orderItem);

                    if ($stock) {
                        app(ReserveStockAction::class)->handle($stock->warehouse, $sellable, $item->quantity, $order->customer, $order);
                    }
                }
            }

            $this->aggregator->recompute($order);

            $order->payments()->create([
                'status' => PaymentStatus::Pending,
                'amount' => $order->total,
            ]);

            $order->invoice()->create([
                'invoice_number' => sprintf('INV-%d-%06d', $order->created_at->year, $order->id),
                'issued_at' => now(),
            ]);

            foreach ($order->vendorOrders as $vendorOrder) {
                $vendorOrder->packingSlip()->create(['generated_at' => now()]);
            }

            $cart->update(['status' => CartStatus::Converted]);
            $lockedSession->update(['order_id' => $order->id]);

            return $order->fresh(['vendorOrders.orderItems', 'payments', 'invoice']);
        });
    }

    private function selectWarehouseStock(Product|ProductVariation $sellable, int $quantity): WarehouseStock
    {
        return $sellable->warehouseStocks()
            ->get()
            ->first(fn (WarehouseStock $stock) => ($stock->on_hand - $stock->reserved) >= $quantity)
            ?? throw new InsufficientStockException('No single warehouse has enough stock to fulfil this item.');
    }
}
