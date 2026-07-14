<?php

namespace App\Actions\Checkout;

use App\Actions\Cart\Concerns\ChecksAvailability;
use App\Actions\Commission\RecordOrderItemCommissionAction;
use App\Actions\Inventory\ReserveStockAction;
use App\Actions\Shipping\CalculateShippingCostAction;
use App\Actions\Tax\CalculateTaxAction;
use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\CheckoutValidationException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ShippingUnavailableException;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItemTaxSnapshot;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\WarehouseStock;
use App\Services\Order\OrderStatusAggregator;
use Illuminate\Support\Collection;
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

            $itemsByVendor = $items->groupBy(fn (CartItem $item) => $item->product->vendor_id);

            $shippingCosts = $itemsByVendor->map(function (Collection $vendorItems) use ($shippingData) {
                $lines = $vendorItems->map(fn (CartItem $item) => [
                    'sellable' => $item->variation ?? $item->product,
                    'quantity' => $item->quantity,
                ]);

                $vendorSubtotal = $vendorItems->sum(fn (CartItem $item) => $item->lineTotal());

                try {
                    return app(CalculateShippingCostAction::class)->handle(
                        $lines,
                        $vendorSubtotal,
                        $shippingData['shipping_country_id'],
                        $shippingData['shipping_state_id'] ?? null,
                        $shippingData['shipping_city_id'] ?? null,
                    );
                } catch (ShippingUnavailableException $e) {
                    throw new CheckoutValidationException($e->getMessage());
                }
            });

            $totalShipping = $shippingCosts->sum();

            $itemTaxes = $items->mapWithKeys(fn (CartItem $item) => [
                $item->id => app(CalculateTaxAction::class)->handle(
                    $item->lineTotal(),
                    $item->product->tax_class_id,
                    $shippingData['shipping_country_id'],
                    $shippingData['shipping_state_id'] ?? null,
                    $shippingData['shipping_city_id'] ?? null,
                    $shippingData['shipping_postal_code'] ?? null,
                ),
            ]);

            $totalTax = $itemTaxes->sum('taxAmount');

            $order = Order::create([
                ...$shippingData,
                'currency_id' => $currency->id,
                'exchange_rate_snapshot' => (float) ($currency->exchangeRate?->rate ?? 1),
                'subtotal' => $cart->subtotal(),
                'discount_amount' => $cart->discount(),
                'shipping_amount' => $totalShipping,
                'tax_amount' => $totalTax,
                'total' => $cart->total() + $totalShipping + $totalTax,
                'coupon_code' => $cart->coupon?->code,
                'status' => OrderStatus::PendingPayment,
                'placed_at' => now(),
            ]);

            foreach ($itemsByVendor as $vendorId => $vendorItems) {
                $vendorSubtotal = $vendorItems->sum(fn (CartItem $item) => $item->lineTotal());
                $vendorShipping = $shippingCosts[$vendorId];
                $vendorTax = $vendorItems->sum(fn (CartItem $item) => $itemTaxes[$item->id]['taxAmount']);

                $vendorOrder = $order->vendorOrders()->create([
                    'vendor_id' => $vendorId,
                    'subtotal' => $vendorSubtotal,
                    // The coupon discount is applied only at the parent-order
                    // level this phase; proportional per-vendor attribution
                    // only matters once commission calculation (Phase 14)
                    // needs it, so vendor_order totals are deliberately
                    // gross (pre-discount) for now.
                    'discount_amount' => 0,
                    'shipping_amount' => $vendorShipping,
                    'tax_amount' => $vendorTax,
                    'total' => $vendorSubtotal + $vendorShipping + $vendorTax,
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

                    $itemTax = $itemTaxes[$item->id];
                    OrderItemTaxSnapshot::create([
                        'order_item_id' => $orderItem->id,
                        'tax_rate_id' => $itemTax['rate']?->id,
                        'rate_percent' => $itemTax['rate']?->rate_percent ?? 0,
                        'taxable_amount' => $item->lineTotal(),
                        'tax_amount' => $itemTax['taxAmount'],
                    ]);

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
