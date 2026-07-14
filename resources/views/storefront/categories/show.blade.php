@extends('layouts.storefront')

@section('title', ($subcategory ?? $category)->name)

@section('content')
    <div class="flex flex-col md:flex-row gap-6">
        @include('storefront.partials.category-sidebar')

        <div class="flex-1 min-w-0">
            <nav class="text-sm text-gray-500 mb-4">
                <a href="{{ route('categories.index') }}" class="hover:text-gray-700">Categories</a>
                <span class="mx-1">/</span>
                @if ($subcategory)
                    <a href="{{ route('categories.show', $category) }}" class="hover:text-gray-700">{{ $category->name }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-gray-700">{{ $subcategory->name }}</span>
                @else
                    <span class="text-gray-700">{{ $category->name }}</span>
                @endif
            </nav>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ ($subcategory ?? $category)->name }}</h1>

            @if (($subcategory ?? $category)->description)
                <p class="text-sm text-gray-600 mb-6">{{ ($subcategory ?? $category)->description }}</p>
            @endif

            @if (! $subcategory && $subcategories->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-6">
                    @foreach ($subcategories as $child)
                        <a href="{{ route('categories.show-subcategory', [$category, $child]) }}" class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-1 text-sm text-gray-700 hover:border-indigo-500 hover:text-indigo-600">
                            {{ $child->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            @include('storefront.partials.product-listing', ['products' => $products, 'categories' => $categories, 'brands' => $brands])
        </div>
    </div>
@endsection
