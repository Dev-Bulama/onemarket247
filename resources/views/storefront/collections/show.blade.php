@extends('layouts.storefront')

@section('title', $collection->name)

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $collection->name }}</h1>

    @if ($collection->description)
        <p class="text-sm text-gray-600 mb-6">{{ $collection->description }}</p>
    @endif

    @include('storefront.partials.product-listing', ['products' => $products, 'categories' => $categories, 'brands' => $brands])
@endsection
