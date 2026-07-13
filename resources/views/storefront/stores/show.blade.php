@extends('layouts.storefront')

@section('title', $store->seo_title ?: $store->name)

@section('meta_description')
    {{ $store->seo_description }}
@endsection

@section('content')
    <div class="bg-white shadow rounded-lg p-8">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">{{ $store->seo_title ?: $store->name }}</h1>
            @if ($store->is_verified)
                <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700">Verified vendor</span>
            @endif
        </div>

        @if ($store->status->value === 'vacation')
            <div class="mt-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-700">
                {{ $store->vacation_message ?: 'This store is temporarily on vacation.' }}
            </div>
        @endif

        @if ($store->description)
            <p class="mt-4 text-gray-700">{{ $store->description }}</p>
        @endif

        <dl class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
            @if ($store->address)
                <div>
                    <dt class="font-medium text-gray-500">Address</dt>
                    <dd class="mt-1 text-gray-900">
                        {{ $store->address }}
                        @if ($store->city)
                            , {{ $store->city->name }}
                        @endif
                        @if ($store->state)
                            , {{ $store->state->name }}
                        @endif
                        @if ($store->country)
                            , {{ $store->country->name }}
                        @endif
                    </dd>
                </div>
            @endif

            <div>
                <dt class="font-medium text-gray-500">Member since</dt>
                <dd class="mt-1 text-gray-900">{{ $store->created_at->format('F Y') }}</dd>
            </div>

            @if (! empty($store->working_hours))
                <div class="sm:col-span-2">
                    <dt class="font-medium text-gray-500">Working hours</dt>
                    <dd class="mt-1 text-gray-900">
                        <ul class="space-y-0.5">
                            @foreach ($store->working_hours as $day => $hours)
                                <li>{{ ucfirst($day) }}: {{ $hours }}</li>
                            @endforeach
                        </ul>
                    </dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="mt-10">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Products from this store</h2>
        @include('storefront.partials.product-listing', ['products' => $products, 'categories' => $categories, 'brands' => $brands])
    </div>
@endsection
