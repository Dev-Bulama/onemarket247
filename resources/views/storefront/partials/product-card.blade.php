@php
    $thumb = $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images');
    $range = $product->displayPriceRange();
    $price = $product->displayPrice();
    $rating = $product->averageRating();
    $reviewCount = $product->approved_reviews_count ?? $product->approvedReviews()->count();
    $discountPercent = ! $range ? $product->discountPercent() : null;
    $onFlashSale = $product->isOnFlashSale();
    $locationLabel = $locationLabel ?? $product->vendor?->store?->city?->name;
@endphp

<div class="group relative bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
    @if ($discountPercent)
        <span class="absolute top-2 left-2 z-10 rounded-full bg-orange-500 px-2 py-0.5 text-xs font-semibold text-white shadow-sm">-{{ $discountPercent }}%</span>
    @endif

    @auth
        <form method="POST" action="{{ route('account.wishlist.store', $product) }}" class="absolute top-2 right-2 z-10">
            @csrf
            <button type="submit" title="Add to wishlist" class="flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-gray-500 shadow-sm hover:text-orange-600">
                <i class="fa-regular fa-heart" aria-hidden="true"></i>
            </button>
        </form>
    @else
        <a href="{{ route('login') }}" title="Sign in to save" class="absolute top-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-gray-500 shadow-sm hover:text-orange-600">
            <i class="fa-regular fa-heart" aria-hidden="true"></i>
        </a>
    @endauth

    <a href="{{ route('products.show', $product) }}" class="block">
        <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
            @if ($thumb)
                <img src="{{ $thumb }}" alt="{{ $product->name }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform" loading="lazy">
            @else
                <span class="text-gray-300 text-3xl"><i class="fa-solid fa-image" aria-hidden="true"></i></span>
            @endif
        </div>
        <div class="p-3">
            @if ($product->brand)
                <p class="text-xs text-gray-500">{{ $product->brand->name }}</p>
            @endif
            <h3 class="text-sm font-medium text-gray-900 line-clamp-2">{{ $product->name }}</h3>

            @if ($rating !== null)
                <div class="mt-1 flex items-center gap-1">
                    <span class="flex text-orange-400 text-xs">
                        @for ($i = 1; $i <= 5; $i++)
                            <span>{{ $i <= round($rating) ? '★' : '☆' }}</span>
                        @endfor
                    </span>
                    <span class="text-xs text-gray-400">({{ $reviewCount }})</span>
                </div>
            @endif

            <div class="mt-1 flex items-center justify-between">
                <p class="text-sm font-semibold {{ $discountPercent ? 'text-orange-600' : 'text-gray-900' }}">
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

            @if ($onFlashSale && $product->manage_stock)
                <p class="mt-1 text-xs text-orange-600">{{ $product->stock_quantity }} units left</p>
            @endif

            @if ($locationLabel)
                <p class="mt-1 flex items-center gap-1 text-xs text-gray-400">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    {{ $locationLabel }}
                </p>
            @endif
        </div>
    </a>
</div>
