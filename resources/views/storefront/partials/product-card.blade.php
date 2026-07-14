@php
    $thumb = $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images');
    $range = $product->displayPriceRange();
    $price = $product->displayPrice();
    $rating = $product->averageRating();
    $reviewCount = $product->approved_reviews_count ?? $product->approvedReviews()->count();
    $discountPercent = (! $range && $product->compare_at_price && $product->compare_at_price > $product->price)
        ? (int) round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100)
        : null;
@endphp

<a href="{{ route('products.show', $product) }}" class="group block bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-shadow relative">
    @if ($discountPercent)
        <span class="absolute top-2 left-2 z-10 rounded bg-amber-500 px-1.5 py-0.5 text-xs font-semibold text-white">-{{ $discountPercent }}%</span>
    @endif
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

        @if ($rating !== null)
            <div class="mt-1 flex items-center gap-1">
                <span class="flex text-amber-400 text-xs">
                    @for ($i = 1; $i <= 5; $i++)
                        <span>{{ $i <= round($rating) ? '★' : '☆' }}</span>
                    @endfor
                </span>
                <span class="text-xs text-gray-400">({{ $reviewCount }})</span>
            </div>
        @endif

        <div class="mt-1 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-900">
                @if ($discountPercent)
                    <span class="text-gray-400 line-through mr-1 font-normal">@price($product->compare_at_price)</span>
                @endif
                @if ($range)
                    From @price($range['min'])
                @elseif ($price !== null)
                    @price($price)
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
