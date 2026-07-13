@extends('layouts.storefront')

@section('title', 'Home')

@section('content')
    <section class="rounded-lg bg-indigo-600 text-white px-8 py-12 text-center">
        <h1 class="text-3xl font-bold">Shop thousands of products from independent vendors</h1>
        <p class="mt-3 text-indigo-100">One marketplace, hundreds of stores.</p>
        <a href="{{ route('shop.index') }}" class="mt-6 inline-flex items-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-indigo-600 hover:bg-indigo-50">
            Browse the shop
        </a>
    </section>

    @if ($categories->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Shop by category</h2>
                <a href="{{ route('categories.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category) }}" class="rounded-lg border border-gray-200 bg-white px-4 py-6 text-center hover:shadow-md transition-shadow">
                        <span class="text-sm font-medium text-gray-900">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($featuredProducts->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Featured products</h2>
                <a href="{{ route('shop.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach ($featuredProducts as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($stores->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Featured stores</h2>
                <a href="{{ route('stores.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($stores as $store)
                    <a href="{{ route('stores.show', $store->slug) }}" class="rounded-lg border border-gray-200 bg-white px-4 py-6 text-center hover:shadow-md transition-shadow">
                        <span class="text-sm font-medium text-gray-900">{{ $store->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
