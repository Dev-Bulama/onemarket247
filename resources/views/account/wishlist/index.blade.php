@extends('layouts.app')

@section('title', 'Wishlist')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Wishlist</h1>

    @if ($products->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-sm text-gray-500">
            Your wishlist is empty. Browse the <a href="{{ route('shop.index') }}" class="text-brand-orange hover:underline">shop</a> to add products.
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($products as $product)
                <div class="relative">
                    @include('storefront.partials.product-card', ['product' => $product])
                    <form method="POST" action="{{ route('account.wishlist.destroy', $product) }}" class="absolute top-2 right-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Remove from wishlist"
                                class="rounded-full bg-white/90 p-1.5 text-gray-500 hover:text-red-600 shadow">
                            &times;
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
@endsection
