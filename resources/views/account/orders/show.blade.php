@extends('layouts.app')

@section('title', 'Order '.$order->order_number)

@php
    $canCancelAny = $order->vendorOrders->contains(
        fn ($vendorOrder) => in_array($vendorOrder->status, \App\Actions\Order\CancelVendorOrderAction::CANCELLABLE_FROM, true)
    );
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">{{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500">Placed {{ $order->placed_at->format('M j, Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">{{ $order->status->getLabel() }}</span>
            <a href="{{ route('orders.invoice', $order) }}" class="text-sm text-indigo-600 hover:underline">Download invoice</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            @foreach ($order->vendorOrders as $vendorOrder)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">{{ $vendorOrder->vendor_order_number }}</span>
                        <span class="text-xs text-gray-500">{{ $vendorOrder->status->getLabel() }}</span>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($vendorOrder->orderItems as $item)
                            <div class="p-4 flex items-center justify-between text-sm">
                                <div>
                                    <p class="text-gray-900">{{ $item->product_name }}</p>
                                    <p class="text-xs text-gray-500">Qty {{ $item->quantity }} &middot; ${{ number_format($item->unit_price / 100, 2) }}</p>
                                </div>
                                <p class="text-gray-900">${{ number_format($item->line_total / 100, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if ($order->notes->isNotEmpty())
                <div class="bg-white shadow rounded-lg p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">Updates about your order</h2>
                    <div class="space-y-3">
                        @foreach ($order->notes as $note)
                            <div class="text-sm text-gray-700">
                                <p>{{ $note->body }}</p>
                                <p class="text-xs text-gray-400">{{ $note->created_at->format('M j, Y g:ia') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($canCancelAny)
                <div class="bg-white shadow rounded-lg p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">Cancel order</h2>
                    <p class="text-xs text-gray-500 mb-3">
                        Cancels every item that hasn't shipped yet. Items already dispatched can't be cancelled here.
                    </p>
                    <form method="POST" action="{{ route('account.orders.cancel', $order) }}" class="flex gap-2">
                        @csrf
                        <input type="text" name="reason" placeholder="Reason for cancelling" required
                               class="flex-1 rounded-md border-gray-300 text-sm shadow-sm">
                        <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                            Cancel eligible items
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="bg-white shadow rounded-lg p-6 h-fit space-y-4">
            <h2 class="font-medium text-gray-900">Shipping to</h2>
            <div class="text-sm text-gray-700 space-y-1">
                <p>{{ $order->shipping_full_name }}</p>
                @if ($order->shipping_phone)
                    <p>{{ $order->shipping_phone }}</p>
                @endif
                <p>{{ $order->shipping_address_line_1 }}</p>
                @if ($order->shipping_address_line_2)
                    <p>{{ $order->shipping_address_line_2 }}</p>
                @endif
                <p>
                    {{ collect([$order->shippingCity?->name, $order->shippingState?->name, $order->shippingCountry?->name, $order->shipping_postal_code])->filter()->implode(', ') }}
                </p>
            </div>

            <dl class="space-y-2 text-sm border-t border-gray-100 pt-4">
                <div class="flex justify-between">
                    <dt class="text-gray-600">Subtotal</dt>
                    <dd class="text-gray-900">${{ number_format($order->subtotal / 100, 2) }}</dd>
                </div>
                @if ($order->discount_amount > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Discount</dt>
                        <dd class="text-green-700">-${{ number_format($order->discount_amount / 100, 2) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-100 pt-2">
                    <dt>Total</dt>
                    <dd>${{ number_format($order->total / 100, 2) }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
