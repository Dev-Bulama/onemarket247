<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Packing slip {{ $vendorOrder->vendor_order_number }}</title>
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
    </style>
</head>
<body>
    <div class="header">
        <div class="col">
            <h1>Packing slip</h1>
            <p class="muted">{{ $vendorOrder->vendor_order_number }}</p>
        </div>
        <div class="col text-right">
            <p><strong>Order {{ $vendorOrder->order->order_number }}</strong></p>
            <p class="muted">Placed {{ $vendorOrder->order->placed_at->format('M j, Y') }}</p>
        </div>
    </div>

    <div class="header">
        <div class="col">
            <p><strong>Ship to</strong></p>
            <p>{{ $vendorOrder->order->shipping_full_name }}</p>
            @if ($vendorOrder->order->shipping_phone)
                <p>{{ $vendorOrder->order->shipping_phone }}</p>
            @endif
            <p>{{ $vendorOrder->order->shipping_address_line_1 }}</p>
            @if ($vendorOrder->order->shipping_address_line_2)
                <p>{{ $vendorOrder->order->shipping_address_line_2 }}</p>
            @endif
            <p>
                {{ collect([$vendorOrder->order->shippingCity?->name, $vendorOrder->order->shippingState?->name, $vendorOrder->order->shippingCountry?->name, $vendorOrder->order->shipping_postal_code])->filter()->implode(', ') }}
            </p>
        </div>
    </div>

    <table>
        <tr>
            <th>Item</th>
            <th>SKU</th>
            <th class="text-right">Qty</th>
        </tr>
        @foreach ($vendorOrder->orderItems as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->sku }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
            </tr>
        @endforeach
    </table>

    <p class="muted" style="margin-top: 24px;">No pricing information is shown on a packing slip.</p>
</body>
</html>
