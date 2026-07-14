@extends('layouts.storefront')

@section('title', 'Shop')

@section('content')
    <div class="flex flex-col md:flex-row gap-6">
        @include('storefront.partials.category-sidebar')

        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Shop</h1>

            @include('storefront.partials.product-listing', ['products' => $products, 'categories' => $categories, 'brands' => $brands])
        </div>
    </div>
@endsection
