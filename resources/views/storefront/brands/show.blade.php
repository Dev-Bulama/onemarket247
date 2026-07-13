@extends('layouts.storefront')

@section('title', $brand->name)

@section('content')
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('brands.index') }}" class="hover:text-gray-700">Brands</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700">{{ $brand->name }}</span>
    </nav>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $brand->name }}</h1>

    @if ($brand->description)
        <p class="text-sm text-gray-600 mb-6">{{ $brand->description }}</p>
    @endif

    @include('storefront.partials.product-listing', ['products' => $products, 'categories' => $categories, 'brands' => $brands])
@endsection
