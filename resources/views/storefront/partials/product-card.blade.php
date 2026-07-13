@php
    $thumb = $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images');
    $range = $product->displayPriceRange();
    $price = $product->displayPrice();
@endphp

<a href="{{ route('products.show', $product) }}" class="group block bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
    <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
        @if ($thumb)
            <img src="{{ $thumb }}" alt="{{ $product->name }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform">
        @else
            <span class="text-gray-300 text-sm">No image</span>
        @endif
    </div>
    <div class="p-3">
        @if ($product->brand)
            <p class="text-xs text-gray-500">{{ $product->brand->name }}</p>
        @endif
        <h3 class="text-sm font-medium text-gray-900 line-clamp-2">{{ $product->name }}</h3>
        <div class="mt-1 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-900">
                @if ($range)
                    From ${{ number_format($range['min'] / 100, 2) }}
                @elseif ($price !== null)
                    ${{ number_format($price / 100, 2) }}
                @else
                    &nbsp;
                @endif
            </p>
            @if (! $product->isInStock())
                <span class="text-xs font-medium text-red-600">Out of stock</span>
            @endif
        </div>
    </div>
</a>
