<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->invoice->invoice_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin-bottom: 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-size: 11px; text-transform: uppercase; color: #6b7280; }
        .text-right { text-align: right; }
        .header { display: table; width: 100%; margin-bottom: 20px; }
        .col { display: table-cell; vertical-align: top; width: 50%; }
        .totals { width: 260px; margin-left: auto; margin-top: 16px; }
        .totals td { border: none; padding: 2px 8px; }
        .totals .grand { font-weight: bold; border-top: 1px solid #1f2937; }
        .vendor-heading { background: #f3f4f6; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="col">
            <h1>Invoice</h1>
            <p class="muted">{{ $order->invoice->invoice_number }}</p>
            <p class="muted">Issued {{ $order->invoice->issued_at->format('M j, Y') }}</p>
        </div>
        <div class="col text-right">
            <p><strong>Order {{ $order->order_number }}</strong></p>
            <p class="muted">Placed {{ $order->placed_at->format('M j, Y') }}</p>
        </div>
    </div>

    <div class="header">
        <div class="col">
            <p><strong>Billed to</strong></p>
            <p>{{ $order->customerName() }}</p>
            <p>{{ $order->customerEmail() }}</p>
        </div>
        <div class="col">
            <p><strong>Shipping address</strong></p>
            <p>{{ $order->shipping_full_name }}</p>
            <p>{{ $order->shipping_address_line_1 }}</p>
            @if ($order->shipping_address_line_2)
                <p>{{ $order->shipping_address_line_2 }}</p>
            @endif
            <p>
                {{ collect([$order->shippingCity?->name, $order->shippingState?->name, $order->shippingCountry?->name, $order->shipping_postal_code])->filter()->implode(', ') }}
            </p>
        </div>
    </div>

    @foreach ($order->vendorOrders as $vendorOrder)
        <table>
            <tr>
                <th colspan="4" class="vendor-heading">{{ $vendorOrder->vendor_order_number }}</th>
            </tr>
            <tr>
                <th>Item</th>
                <th class="text-right">Unit price</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Total</th>
            </tr>
            @foreach ($vendorOrder->orderItems as $item)
                <tr>
                    <td>{{ $item->product_name }} <span class="muted">({{ $item->sku }})</span></td>
                    <td class="text-right">${{ number_format($item->unit_price / 100, 2) }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->line_total / 100, 2) }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">${{ number_format($order->subtotal / 100, 2) }}</td>
        </tr>
        @if ($order->discount_amount > 0)
            <tr>
                <td>Discount @if ($order->coupon_code) ({{ $order->coupon_code }}) @endif</td>
                <td class="text-right">-${{ number_format($order->discount_amount / 100, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td>Shipping</td>
            <td class="text-right">${{ number_format($order->shipping_amount / 100, 2) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-right">${{ number_format($order->tax_amount / 100, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Total</td>
            <td class="text-right">${{ number_format($order->total / 100, 2) }}</td>
        </tr>
    </table>
</body>
</html>
