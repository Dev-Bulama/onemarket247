@extends('layouts.guest')

@section('title', 'Delivery tracking')

@section('content')
    @php
        $vendorOrder = $assignment->shipment->vendorOrder;
    @endphp

    <h1 class="text-lg font-semibold text-gray-900 mb-2">Delivery tracking</h1>
    <p class="text-sm text-gray-600 mb-4">Order {{ $vendorOrder->vendor_order_number }}</p>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <p class="text-sm text-gray-700 mb-6">
        Current status: <span class="font-medium">{{ $assignment->status->getLabel() }}</span>
    </p>

    @if (count($nextStatuses) > 0)
        <form method="POST" action="{{ route('deliveries.track.advance', $assignment->tracking_token) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            @if (in_array(\App\Enums\DeliveryAssignmentStatus::Delivered, $nextStatuses, true))
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recipient name (optional)</label>
                    <input type="text" name="recipient_name" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proof of delivery photo (optional)</label>
                    <input type="file" name="photo" accept="image/*" capture="environment" class="w-full text-sm">
                </div>
            @endif

            <div class="space-y-2">
                @foreach ($nextStatuses as $status)
                    <button type="submit" name="status" value="{{ $status->value }}"
                        class="w-full rounded-md {{ $status->value === 'failed' ? 'bg-red-600' : 'bg-brand-orange' }} text-white font-medium text-sm py-2.5">
                        Mark as {{ $status->getLabel() }}
                    </button>
                @endforeach
            </div>
        </form>
    @else
        <p class="text-sm text-gray-600">This delivery is complete. Thanks for your work!</p>
    @endif
@endsection
