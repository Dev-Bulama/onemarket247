@extends('layouts.app')

@section('title', 'Track order '.$order->order_number)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Tracking for {{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500">Placed {{ $order->placed_at->format('M j, Y') }}</p>
        </div>
        <a href="{{ route('account.orders.show', $order) }}" class="text-sm text-brand-orange hover:underline">Back to order</a>
    </div>

    <div class="space-y-6">
        @foreach ($order->vendorOrders as $vendorOrder)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-gray-700">{{ $vendorOrder->vendor_order_number }}</span>
                        <span class="text-xs text-gray-500">&middot; {{ $vendorOrder->vendor->business_name }}</span>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-700">{{ $vendorOrder->status->getLabel() }}</span>
                </div>

                @if ($vendorOrder->shipments->isEmpty())
                    <div class="p-4 text-sm text-gray-500">Not yet shipped.</div>
                @else
                    @foreach ($vendorOrder->shipments as $shipment)
                        <div class="p-4 border-b border-gray-100 last:border-b-0">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <div class="text-sm text-gray-700">
                                    @if ($shipment->carrier)
                                        <span class="font-medium">{{ $shipment->carrier->name }}</span>
                                    @endif
                                    @if ($shipment->tracking_number)
                                        &middot;
                                        @php $trackingUrl = $shipment->carrier?->trackingUrlFor($shipment->tracking_number); @endphp
                                        @if ($trackingUrl)
                                            <a href="{{ $trackingUrl }}" target="_blank" rel="noopener" class="text-brand-orange hover:underline">{{ $shipment->tracking_number }}</a>
                                        @else
                                            <span>{{ $shipment->tracking_number }}</span>
                                        @endif
                                    @endif
                                    @if ($shipment->pickupStation)
                                        &middot; Pickup at {{ $shipment->pickupStation->name }}
                                    @endif
                                </div>
                                @if ($shipment->estimated_delivery_at)
                                    <span class="text-xs text-gray-500">Estimated delivery {{ $shipment->estimated_delivery_at->format('M j, Y') }}</span>
                                @endif
                            </div>

                            <ol class="space-y-3">
                                @foreach ($shipment->events->sortByDesc('occurred_at') as $event)
                                    <li class="flex gap-3 text-sm">
                                        <span class="mt-1 h-2 w-2 flex-shrink-0 rounded-full {{ $loop->first ? 'bg-brand-orange' : 'bg-gray-300' }}"></span>
                                        <div>
                                            <p class="text-gray-900">{{ $event->status->getLabel() }}</p>
                                            @if ($event->location || $event->description)
                                                <p class="text-xs text-gray-500">{{ collect([$event->location, $event->description])->filter()->implode(' — ') }}</p>
                                            @endif
                                            <p class="text-xs text-gray-400">{{ $event->occurred_at->format('M j, Y g:ia') }}</p>
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
@endsection
