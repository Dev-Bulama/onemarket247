@extends('layouts.storefront')

@section('title', $product->seo_title ?: $product->name)

@section('meta_description')
    {{ $product->seo_description ?: $product->short_description }}
@endsection

@php
    $images = $product->getMedia('images');
    $range = $product->displayPriceRange();
    $price = $product->displayPrice();
@endphp

@section('content')
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('shop.index') }}" class="hover:text-gray-700">Shop</a>
        @if ($product->primaryCategory())
            <span class="mx-1">/</span>
            <a href="{{ route('categories.show', $product->primaryCategory()) }}" class="hover:text-gray-700">{{ $product->primaryCategory()->name }}</a>
        @endif
        <span class="mx-1">/</span>
        <span class="text-gray-700">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <div>
            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                @if ($images->isNotEmpty())
                    <img src="{{ $images->first()->getUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                @else
                    <span class="text-gray-300 text-sm">No image</span>
                @endif
            </div>

            @if ($images->count() > 1)
                <div class="mt-3 grid grid-cols-5 gap-2">
                    @foreach ($images as $image)
                        <div class="aspect-square bg-gray-100 rounded overflow-hidden">
                            <img src="{{ $image->getUrl('thumb') ?: $image->getUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            @if ($product->brand)
                <p class="text-sm text-gray-500">{{ $product->brand->name }}</p>
            @endif
            <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>

            <div class="mt-3 flex items-center gap-3">
                <p class="text-2xl font-semibold text-gray-900">
                    @if ($range)
                        ${{ number_format($range['min'] / 100, 2) }} – ${{ number_format($range['max'] / 100, 2) }}
                    @elseif ($price !== null)
                        ${{ number_format($price / 100, 2) }}
                    @endif
                </p>

                @if ($product->isInStock())
                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">In stock</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">Out of stock</span>
                @endif
            </div>

            @if ($product->short_description)
                <p class="mt-4 text-gray-700">{{ $product->short_description }}</p>
            @endif

            @if ($product->vendor?->store)
                <p class="mt-4 text-sm text-gray-600">
                    Sold by
                    <a href="{{ route('stores.show', $product->vendor->store->slug) }}" class="font-medium text-indigo-600 hover:underline">{{ $product->vendor->store->name }}</a>
                </p>
            @endif

            @if ($product->variations->isNotEmpty())
                <div class="mt-6">
                    <h2 class="text-sm font-semibold text-gray-900">Available options</h2>
                    <div class="mt-2 divide-y divide-gray-200 border border-gray-200 rounded-md">
                        @foreach ($product->variations as $variation)
                            <div class="flex items-center justify-between px-3 py-2 text-sm">
                                <span class="text-gray-700">{{ $variation->attributeValues->pluck('value')->implode(' / ') }}</span>
                                <span class="flex items-center gap-3">
                                    <span class="font-medium text-gray-900">${{ number_format($variation->price / 100, 2) }}</span>
                                    <span class="text-xs {{ $variation->isInStock() ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $variation->isInStock() ? 'In stock' : 'Out of stock' }}
                                    </span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6 rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600">
                Ordering isn't open yet — cart and checkout are coming in a later release.
            </div>

            @if ($product->categories->isNotEmpty())
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($product->categories as $category)
                        <a href="{{ route('categories.show', $category) }}" class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-1 text-xs text-gray-700 hover:border-indigo-500 hover:text-indigo-600">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($product->description)
        <div class="mt-12 border-t border-gray-200 pt-8">
            <h2 class="text-lg font-semibold text-gray-900">Description</h2>
            <div class="mt-3 text-sm text-gray-700 whitespace-pre-line">{{ $product->description }}</div>
        </div>
    @endif
@endsection
