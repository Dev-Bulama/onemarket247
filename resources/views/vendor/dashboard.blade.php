@extends('layouts.vendor')

@section('title', 'Vendor dashboard')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Store account</h1>

    <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <div>
            <div class="text-sm text-gray-500">Business name</div>
            <div class="text-gray-900">{{ $vendor->business_name }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Status</div>
            <div class="text-gray-900">{{ $vendor->status->getLabel() }}</div>
        </div>
        @if ($vendor->store)
            <div>
                <div class="text-sm text-gray-500">Store</div>
                <div class="text-gray-900">{{ $vendor->store->name }}</div>
            </div>
        @endif
    </div>

    <p class="mt-6 text-sm text-gray-500">
        The full store dashboard (products, orders, earnings) is built in a later phase.
    </p>
@endsection
