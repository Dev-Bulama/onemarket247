@extends('layouts.storefront')

@section('title', $product->seo_title ?: $product->name)

@section('meta_description')
    {{ $product->seo_description ?: $product->short_description }}
@endsection

@php
    $images = $product->getMedia('images');
    $range = $product->displayPriceRange();
    $price = $product->displayPrice();
    $canOrder = $product->variations->isNotEmpty()
        ? $product->variations->where('is_active', true)->contains(fn ($variation) => $variation->isInStock())
        : $product->isInStock();
@endphp

@section('content')
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('shop.index') }}" class="hover:text-gray-700">Shop</a>
        @if ($product->primaryCategory())
            <span class="mx-1">/</span>
            <a href="{{ route('categories.show', $product->primaryCategory()) }}" class="hover:text-gray-700">{{ $product->primaryCategory()->name }}</a>
        @endif
        <span class="mx-1">/</span>
        <span class="text-gray-700">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <div>
            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                @if ($images->isNotEmpty())
                    <img src="{{ $images->first()->getUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                @else
                    <span class="text-gray-300 text-sm">No image</span>
                @endif
            </div>

            @if ($images->count() > 1)
                <div class="mt-3 grid grid-cols-5 gap-2">
                    @foreach ($images as $image)
                        <div class="aspect-square bg-gray-100 rounded overflow-hidden">
                            <img src="{{ $image->getUrl('thumb') ?: $image->getUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            @if ($product->brand)
                <p class="text-sm text-gray-500">{{ $product->brand->name }}</p>
            @endif
            <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>

            @php $averageRating = $product->averageRating(); @endphp
            <a href="#reviews" class="mt-1 inline-flex items-center gap-1 text-sm text-gray-600 hover:text-indigo-600">
                @if ($averageRating !== null)
                    <span class="text-amber-500">★</span> {{ $averageRating }} / 5
                    <span class="text-gray-400">({{ $product->approvedReviews->count() }} {{ Str::plural('review', $product->approvedReviews->count()) }})</span>
                @else
                    <span class="text-gray-400">No reviews yet</span>
                @endif
            </a>

            <div class="mt-3 flex items-center gap-3">
                <p class="text-2xl font-semibold text-gray-900">
                    @if ($range)
                        ${{ number_format($range['min'] / 100, 2) }} – ${{ number_format($range['max'] / 100, 2) }}
                    @elseif ($price !== null)
                        ${{ number_format($price / 100, 2) }}
                    @endif
                </p>

                @if ($product->isInStock())
                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">In stock</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">Out of stock</span>
                @endif
            </div>

            @if ($product->short_description)
                <p class="mt-4 text-gray-700">{{ $product->short_description }}</p>
            @endif

            @if ($product->vendor?->store)
                <p class="mt-4 text-sm text-gray-600">
                    Sold by
                    <a href="{{ route('stores.show', $product->vendor->store->slug) }}" class="font-medium text-indigo-600 hover:underline">{{ $product->vendor->store->name }}</a>
                </p>
            @endif

            @if ($product->variations->isNotEmpty())
                <div class="mt-6">
                    <h2 class="text-sm font-semibold text-gray-900">Available options</h2>
                    <div class="mt-2 divide-y divide-gray-200 border border-gray-200 rounded-md">
                        @foreach ($product->variations as $variation)
                            <div class="flex items-center justify-between px-3 py-2 text-sm">
                                <span class="text-gray-700">{{ $variation->attributeValues->pluck('value')->implode(' / ') }}</span>
                                <span class="flex items-center gap-3">
                                    <span class="font-medium text-gray-900">${{ number_format($variation->price / 100, 2) }}</span>
                                    <span class="text-xs {{ $variation->isInStock() ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $variation->isInStock() ? 'In stock' : 'Out of stock' }}
                                    </span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($canOrder)
                <form method="POST" action="{{ route('cart.items.store') }}" class="mt-6 flex items-end gap-3">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if ($product->variations->isNotEmpty())
                        <div>
                            <label for="product_variation_id" class="block text-sm font-medium text-gray-700">Option</label>
                            <select id="product_variation_id" name="product_variation_id" required
                                    class="mt-1 block rounded-md border-gray-300 shadow-sm">
                                <option value="">Choose an option</option>
                                @foreach ($product->variations->where('is_active', true) as $variation)
                                    <option value="{{ $variation->id }}" @disabled(! $variation->isInStock())>
                                        {{ $variation->attributeValues->pluck('value')->implode(' / ') }}
                                        (${{ number_format($variation->price / 100, 2) }})
                                        @if (! $variation->isInStock()) — out of stock @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">Qty</label>
                        <input id="quantity" type="number" name="quantity" value="1" min="1" class="mt-1 block w-20 rounded-md border-gray-300 shadow-sm">
                    </div>

                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                        Add to cart
                    </button>
                </form>
            @else
                <div class="mt-6 rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    This product is currently out of stock.
                </div>
            @endif

            @auth
                <div class="mt-4 flex items-center gap-3">
                    <form method="POST" action="{{ route('account.wishlist.store', $product) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:border-indigo-500 hover:text-indigo-600">
                            Add to wishlist
                        </button>
                    </form>
                    <form method="POST" action="{{ route('account.compare.store', $product) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:border-indigo-500 hover:text-indigo-600">
                            Add to compare
                        </button>
                    </form>
                </div>
            @else
                <p class="mt-4 text-sm text-gray-500">
                    <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Log in</a> to save this product to your wishlist or compare list.
                </p>
            @endauth

            @if ($product->categories->isNotEmpty())
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($product->categories as $category)
                        <a href="{{ route('categories.show', $category) }}" class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-1 text-xs text-gray-700 hover:border-indigo-500 hover:text-indigo-600">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($product->description)
        <div class="mt-12 border-t border-gray-200 pt-8">
            <h2 class="text-lg font-semibold text-gray-900">Description</h2>
            <div class="mt-3 text-sm text-gray-700 whitespace-pre-line">{{ $product->description }}</div>
        </div>
    @endif

    <div id="reviews" class="mt-12 border-t border-gray-200 pt-8">
        <h2 class="text-lg font-semibold text-gray-900">Reviews</h2>

        @if ($product->approvedReviews->isEmpty())
            <p class="mt-3 text-sm text-gray-500">No reviews yet. Be the first to review this product.</p>
        @else
            <div class="mt-4 space-y-6">
                @foreach ($product->approvedReviews as $review)
                    <div class="border-b border-gray-100 pb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-amber-500">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                                @if ($review->title)
                                    <span class="ml-2 font-medium text-gray-900">{{ $review->title }}</span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-400">{{ $review->created_at->format('M j, Y') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ $review->customer->name }}</p>
                        <p class="mt-2 text-sm text-gray-700">{{ $review->body }}</p>

                        @if ($review->vendor_response)
                            <div class="mt-3 rounded-md bg-gray-50 px-4 py-3">
                                <p class="text-xs font-medium text-gray-600">Response from the seller</p>
                                <p class="mt-1 text-sm text-gray-700">{{ $review->vendor_response }}</p>
                            </div>
                        @endif

                        <div class="mt-3 flex items-center gap-3 text-xs text-gray-500">
                            <span>{{ $review->helpful_count }} {{ Str::plural('person', $review->helpful_count) }} found this helpful</span>
                            @auth
                                @if (! $review->votes->firstWhere('customer_id', auth()->id()))
                                    <form method="POST" action="{{ route('reviews.vote', $review) }}">
                                        @csrf
                                        <button type="submit" class="text-indigo-600 hover:underline">Helpful?</button>
                                    </form>
                                @endif
                            @endauth
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @auth
            @if ($userReview)
                <div class="mt-6 rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    You reviewed this product — status:
                    <span class="font-medium">{{ $userReview->status->getLabel() }}</span>
                    @if ($userReview->status->value === 'rejected' && $userReview->rejection_reason)
                        <p class="mt-1 text-xs text-gray-500">{{ $userReview->rejection_reason }}</p>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('products.reviews.store', $product) }}" class="mt-6 max-w-lg space-y-3">
                    @csrf
                    <h3 class="text-sm font-semibold text-gray-900">Write a review</h3>

                    <div>
                        <label for="rating" class="block text-sm font-medium text-gray-700">Rating</label>
                        <select id="rating" name="rating" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">—</option>
                            @for ($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" @selected(old('rating') == $i)>{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Title (optional)</label>
                        <input id="title" type="text" name="title" value="{{ old('title') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div>
                        <label for="body" class="block text-sm font-medium text-gray-700">Review</label>
                        <textarea id="body" name="body" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('body') }}</textarea>
                    </div>

                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                        Submit review
                    </button>

                    <p class="text-xs text-gray-500">Your review will be published after moderation.</p>
                </form>
            @endif
        @else
            <p class="mt-6 text-sm text-gray-500">
                <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Log in</a> to write a review.
            </p>
        @endauth
    </div>

    <div class="mt-12 border-t border-gray-200 pt-8">
        <h2 class="text-lg font-semibold text-gray-900">Questions &amp; answers</h2>

        @if ($product->questions->isEmpty())
            <p class="mt-3 text-sm text-gray-500">No questions yet.</p>
        @else
            <div class="mt-4 space-y-6">
                @foreach ($product->questions as $question)
                    <div class="border-b border-gray-100 pb-6">
                        <p class="text-sm font-medium text-gray-900">Q: {{ $question->question }}</p>
                        <p class="text-xs text-gray-500">Asked by {{ $question->customer->name }} &middot; {{ $question->created_at->format('M j, Y') }}</p>

                        @foreach ($question->answers as $answer)
                            <div class="mt-3 ml-4 rounded-md bg-gray-50 px-4 py-3">
                                <p class="text-sm text-gray-700">A: {{ $answer->answer }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $answer->answeredBy->name }} &middot; {{ $answer->created_at->format('M j, Y') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif

        @auth
            <form method="POST" action="{{ route('products.questions.store', $product) }}" class="mt-6 max-w-lg space-y-3">
                @csrf
                <h3 class="text-sm font-semibold text-gray-900">Ask a question</h3>

                <div>
                    <label for="question" class="block text-sm font-medium text-gray-700">Your question</label>
                    <textarea id="question" name="question" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('question') }}</textarea>
                </div>

                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                    Ask question
                </button>

                <p class="text-xs text-gray-500">The seller will be notified and can answer here.</p>
            </form>
        @else
            <p class="mt-6 text-sm text-gray-500">
                <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Log in</a> to ask a question.
            </p>
        @endauth
    </div>
@endsection
