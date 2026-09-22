@extends('layouts.storefront')

@section('title', $term !== '' ? 'Search results for "'.$term.'"' : 'Search')

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-6">
        @if ($term !== '')
            Search results for &ldquo;{{ $term }}&rdquo;
        @else
            Search
        @endif
    </h1>

    @if ($spotlightProducts->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-3">Spotlight</h2>
            <div class="flex gap-4 overflow-x-auto pb-2 snap-x snap-mandatory">
                @foreach ($spotlightProducts as $product)
                    <div class="w-40 sm:w-48 flex-none snap-start">
                        @include('storefront.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($term === '')
        <p class="text-sm text-gray-600">Enter a search term above to find products.</p>
    @else
        @include('storefront.partials.product-listing', ['products' => $products, 'categories' => $categories, 'brands' => $brands])
    @endif
@endsection
