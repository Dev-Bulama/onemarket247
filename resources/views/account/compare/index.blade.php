@extends('layouts.app')

@section('title', 'Compare products')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Compare products</h1>

    @if ($products->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-sm text-gray-500">
            You haven't added any products to compare yet. Browse the <a href="{{ route('shop.index') }}" class="text-indigo-600 hover:underline">shop</a> to add some.
        </div>
    @else
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <th class="p-4 text-left text-gray-500 font-medium align-top w-32">Product</th>
                        @foreach ($products as $product)
                            @php
                                $thumb = $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images');
                            @endphp
                            <td class="p-4 align-top min-w-[200px]">
                                <div class="aspect-square bg-gray-100 rounded-md overflow-hidden mb-2">
                                    @if ($thumb)
                                        <img src="{{ $thumb }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <a href="{{ route('products.show', $product) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                    {{ $product->name }}
                                </a>
                                <form method="POST" action="{{ route('account.compare.destroy', $product) }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="p-4 text-left text-gray-500 font-medium">Brand</th>
                        @foreach ($products as $product)
                            <td class="p-4">{{ $product->brand?->name ?? '—' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="p-4 text-left text-gray-500 font-medium">Category</th>
                        @foreach ($products as $product)
                            <td class="p-4">{{ $product->primaryCategory()?->name ?? '—' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="p-4 text-left text-gray-500 font-medium">Price</th>
                        @foreach ($products as $product)
                            @php $price = $product->displayPrice(); @endphp
                            <td class="p-4">{{ $price !== null ? '$'.number_format($price / 100, 2) : '—' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="p-4 text-left text-gray-500 font-medium">Rating</th>
                        @foreach ($products as $product)
                            <td class="p-4">{{ $product->averageRating() !== null ? $product->averageRating().' / 5' : 'No reviews yet' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="p-4 text-left text-gray-500 font-medium">Availability</th>
                        @foreach ($products as $product)
                            <td class="p-4">
                                @if ($product->isInStock())
                                    <span class="text-green-700">In stock</span>
                                @else
                                    <span class="text-red-600">Out of stock</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection
