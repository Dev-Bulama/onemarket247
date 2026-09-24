@extends('layouts.guest')

@section('title', 'Delivery request')

@section('content')
    @php
        $deliveryRequest = $notification->deliveryRequest;
        $shipment = $deliveryRequest->shipment;
        $vendorOrder = $shipment->vendorOrder;
        $order = $vendorOrder->order;
    @endphp

    <h1 class="text-lg font-semibold text-gray-900 mb-2">Delivery request</h1>

    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($deliveryRequest->status->value !== 'pending')
        <p class="text-sm text-gray-600">
            This delivery has already been accepted by another partner. Thanks for checking in — keep an eye out for the next alert.
        </p>
    @else
        <dl class="text-sm text-gray-700 space-y-2 mb-6">
            <div><dt class="inline font-medium">Order:</dt> <dd class="inline">{{ $vendorOrder->vendor_order_number }}</dd></div>
            <div><dt class="inline font-medium">Deliver to:</dt> <dd class="inline">{{ $order->shippingCity?->name }}, {{ $order->shippingState?->name }}</dd></div>
            <div><dt class="inline font-medium">Earnings:</dt> <dd class="inline">{{ \App\Support\PriceDisplay::format($deliveryRequest->delivery_fee) }}</dd></div>
            @if ($deliveryRequest->required_by)
                <div><dt class="inline font-medium">Required by:</dt> <dd class="inline">{{ $deliveryRequest->required_by->format('F j, Y g:i A') }}</dd></div>
            @endif
            @if ($deliveryRequest->special_instructions)
                <div><dt class="inline font-medium">Instructions:</dt> <dd class="inline">{{ $deliveryRequest->special_instructions }}</dd></div>
            @endif
        </dl>

        <form method="POST" action="{{ route('delivery-requests.accept.store', $notification->token) }}">
            @csrf
            <button type="submit" class="w-full rounded-md bg-brand-orange text-white font-medium text-sm py-2.5">
                Accept this delivery
            </button>
        </form>
    @endif
@endsection
