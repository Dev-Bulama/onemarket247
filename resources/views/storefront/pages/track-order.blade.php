@extends('layouts.storefront')

@section('title', 'Track Order')

@section('content')
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-ink">Track your order</h1>
        <p class="mt-1 text-sm text-body">Enter your order number and the email address used at checkout.</p>

        <form method="GET" action="{{ route('pages.track-order') }}" class="mt-6 flex flex-col sm:flex-row gap-3">
            <input
                type="text"
                name="order_number"
                value="{{ $orderNumber }}"
                placeholder="Order number (e.g. OM-2026-000123)"
                class="flex-1 rounded-md border border-line px-3 py-2 text-sm focus:border-brand-orange focus:outline-none focus:ring-1 focus:ring-brand-orange"
                required
            >
            <input
                type="email"
                name="email"
                value="{{ $email }}"
                placeholder="Email used at checkout"
                class="flex-1 rounded-md border border-line px-3 py-2 text-sm focus:border-brand-orange focus:outline-none focus:ring-1 focus:ring-brand-orange"
                required
            >
            <button type="submit" class="rounded-md bg-brand-orange px-5 py-2 text-sm font-semibold text-white hover:bg-brand-orange2">
                Track
            </button>
        </form>

        @if ($notFound)
            <div class="mt-8 rounded-lg border border-dashed border-line bg-white px-6 py-10 text-center">
                <i class="fa-solid fa-box-open text-3xl text-muted" aria-hidden="true"></i>
                <p class="mt-3 text-sm text-body">We couldn't find an order matching that order number and email. Double-check both and try again.</p>
            </div>
        @endif

        @if ($order)
            <div class="mt-8 space-y-6">
                <div class="rounded-lg border border-line bg-white p-4">
                    <p class="text-sm font-semibold text-ink">{{ $order->order_number }}</p>
                    <p class="text-xs text-muted">Placed {{ $order->placed_at?->format('M j, Y') }}</p>
                </div>

                @foreach ($order->vendorOrders as $vendorOrder)
                    <div class="bg-white border border-line rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-line flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-ink">{{ $vendorOrder->vendor_order_number }}</span>
                                <span class="text-xs text-muted">&middot; {{ $vendorOrder->vendor->business_name }}</span>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-surface px-3 py-1 text-xs text-body">{{ $vendorOrder->status->getLabel() }}</span>
                        </div>

                        @if ($vendorOrder->shipments->isEmpty())
                            <div class="p-4 text-sm text-body">Not yet shipped.</div>
                        @else
                            @foreach ($vendorOrder->shipments as $shipment)
                                <div class="p-4 border-b border-line last:border-b-0">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3 text-sm text-body">
                                        @if ($shipment->carrier)
                                            <span class="font-medium">{{ $shipment->carrier->name }}</span>
                                        @endif
                                        @if ($shipment->tracking_number)
                                            <span>{{ $shipment->tracking_number }}</span>
                                        @endif
                                        @if ($shipment->estimated_delivery_at)
                                            <span class="text-xs text-muted">Estimated delivery {{ $shipment->estimated_delivery_at->format('M j, Y') }}</span>
                                        @endif
                                    </div>

                                    <ol class="space-y-3">
                                        @foreach ($shipment->events->sortByDesc('occurred_at') as $event)
                                            <li class="flex gap-3 text-sm">
                                                <span class="mt-1 h-2 w-2 flex-shrink-0 rounded-full {{ $loop->first ? 'bg-brand-green' : 'bg-gray-300' }}"></span>
                                                <div>
                                                    <p class="text-ink">{{ $event->status->getLabel() }}</p>
                                                    <p class="text-xs text-muted">{{ $event->occurred_at->format('M j, Y g:ia') }}</p>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ol>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
