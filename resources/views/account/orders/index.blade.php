@extends('layouts.app')

@section('title', 'Order history')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Order history</h1>

    @if ($orders->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-sm text-gray-500">
            You haven't placed any orders yet. Browse the <a href="{{ route('shop.index') }}" class="text-indigo-600 hover:underline">shop</a> to get started.
        </div>
    @else
        <div class="bg-white shadow rounded-lg overflow-hidden divide-y divide-gray-100">
            @foreach ($orders as $order)
                <a href="{{ route('account.orders.show', $order) }}" class="block p-4 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ $order->order_number }}</p>
                            <p class="text-xs text-gray-500">Placed {{ $order->placed_at->format('M j, Y') }} &middot; {{ $order->vendorOrders->count() }} {{ Str::plural('seller', $order->vendorOrders->count()) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">${{ number_format($order->total / 100, 2) }}</p>
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ $order->status->getLabel() }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
@endsection
