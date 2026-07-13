@extends('layouts.storefront')

@section('title', 'Brands')

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Brands</h1>

    @if ($brands->isEmpty())
        <p class="text-sm text-gray-600">No brands yet.</p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($brands as $brand)
                <a href="{{ route('brands.show', $brand) }}" class="rounded-lg border border-gray-200 bg-white px-4 py-6 text-center hover:shadow-md transition-shadow">
                    <span class="text-sm font-medium text-gray-900">{{ $brand->name }}</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
