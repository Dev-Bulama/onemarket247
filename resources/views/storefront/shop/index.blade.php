@extends('layouts.storefront')

@section('title', 'Shop')

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Shop</h1>

    @include('storefront.partials.product-listing', ['products' => $products, 'categories' => $categories, 'brands' => $brands])
@endsection
