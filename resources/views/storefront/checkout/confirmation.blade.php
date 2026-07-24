@extends('layouts.storefront')

@section('title', 'Order confirmed')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow rounded-lg p-6">
            <h1 class="text-lg font-semibold text-gray-900">Thank you — your order has been placed</h1>
            <p class="mt-1 text-sm text-gray-600">Order number <span class="font-medium text-gray-900">{{ $order->order_number }}</span></p>

            @if ($errors->has('payment'))
                <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first('payment') }}
                </div>
            @endif

            @if ($payment?->status === \App\Enums\PaymentStatus::Paid)
                <div class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    Payment received — thank you! Your order status is currently
                    <span class="font-medium">{{ $order->status->getLabel() }}</span>.
                </div>
            @elseif ($payment && in_array($payment->status, [\App\Enums\PaymentStatus::Failed, \App\Enums\PaymentStatus::Cancelled], true))
                <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p>Your last payment attempt didn't go through.</p>
                    <form method="POST" action="{{ route('checkout.payment.initialize', $order) }}" class="mt-2">
                        @csrf
                        <button type="submit" class="rounded-md bg-brand-orange px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-orange">
                            Try again
                        </button>
                    </form>
                </div>
            @elseif ($payment)
                <div class="mt-4 rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    <p>Your items are reserved. Complete payment to confirm your order.</p>
                    <form method="POST" action="{{ route('checkout.payment.initialize', $order) }}" class="mt-2">
                        @csrf
                        <button type="submit" class="rounded-md bg-brand-orange px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-orange">
                            Pay now
                        </button>
                    </form>
                </div>
            @endif

            <div class="mt-6">
                <h2 class="text-sm font-semibold text-gray-900">Shipping to</h2>
                <p class="mt-1 text-sm text-gray-700">{{ $order->shipping_full_name }}</p>
                @if ($order->shipping_phone)
                    <p class="text-sm text-gray-700">{{ $order->shipping_phone }}</p>
                @endif
                <p class="text-sm text-gray-700">{{ $order->shipping_address_line_1 }}</p>
                @if ($order->shipping_address_line_2)
                    <p class="text-sm text-gray-700">{{ $order->shipping_address_line_2 }}</p>
                @endif
                <p class="text-sm text-gray-700">
                    {{ collect([$order->shippingCity?->name, $order->shippingState?->name, $order->shippingCountry?->name, $order->shipping_postal_code])->filter()->implode(', ') }}
                </p>
            </div>

            <div class="mt-6 space-y-6">
                @foreach ($order->vendorOrders as $vendorOrder)
                    <div class="border border-gray-200 rounded-md overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 text-sm font-medium text-gray-700 flex items-center justify-between">
                            <span>{{ $vendorOrder->vendor_order_number }}</span>
                            <span class="text-xs text-gray-500">{{ $vendorOrder->status->getLabel() }}</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach ($vendorOrder->orderItems as $item)
                                <div class="px-4 py-3 flex items-center justify-between text-sm">
                                    <div>
                                        <p class="text-gray-900">{{ $item->product_name }}</p>
                                        <p class="text-xs text-gray-500">Qty {{ $item->quantity }} &middot; @price($item->unit_price)</p>
                                    </div>
                                    <p class="text-gray-900">@price($item->line_total)</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <dl class="mt-6 space-y-2 text-sm border-t border-gray-100 pt-4">
                <div class="flex justify-between">
                    <dt class="text-gray-600">Subtotal</dt>
                    <dd class="text-gray-900">@price($order->subtotal)</dd>
                </div>
                @if ($order->discount_amount > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Discount ({{ $order->coupon_code }})</dt>
                        <dd class="text-green-700">-@price($order->discount_amount)</dd>
                    </div>
                @endif
                <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-100 pt-2">
                    <dt>Total</dt>
                    <dd>@price($order->total)</dd>
                </div>
            </dl>
        </div>

        <p class="mt-6 text-center text-sm text-gray-500">
            <a href="{{ route('shop.index') }}" class="text-brand-orange hover:underline">Continue shopping</a>
        </p>
    </div>
@endsection
