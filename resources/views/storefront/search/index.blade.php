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

    @if ($term === '')
        <p class="text-sm text-gray-600">Enter a search term above to find products.</p>
    @else
        @include('storefront.partials.product-listing', ['products' => $products, 'categories' => $categories, 'brands' => $brands])
    @endif
@endsection
