@extends('layouts.storefront')

@section('title', 'Home')

@section('content')
    {{-- Hero slider: dependency-free, auto-rotating, no JS framework required --}}
    <section class="relative overflow-hidden rounded-lg" id="hero-slider">
        <div class="relative h-64 sm:h-80">
            <div class="hero-slide absolute inset-0 flex flex-col items-center justify-center bg-indigo-600 px-8 text-center text-white transition-opacity duration-700">
                <h1 class="text-2xl sm:text-3xl font-bold">Shop thousands of products from independent vendors</h1>
                <p class="mt-3 text-indigo-100">One marketplace, hundreds of stores.</p>
                <a href="{{ route('shop.index') }}" class="mt-6 inline-flex items-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-indigo-600 hover:bg-indigo-50">
                    Browse the shop
                </a>
            </div>
            <div class="hero-slide absolute inset-0 flex flex-col items-center justify-center bg-emerald-600 px-8 text-center text-white opacity-0 transition-opacity duration-700">
                <h1 class="text-2xl sm:text-3xl font-bold">Discover every category</h1>
                <p class="mt-3 text-emerald-100">From electronics to fashion, find exactly what you need.</p>
                <a href="{{ route('categories.index') }}" class="mt-6 inline-flex items-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-emerald-600 hover:bg-emerald-50">
                    Explore categories
                </a>
            </div>
            <div class="hero-slide absolute inset-0 flex flex-col items-center justify-center bg-amber-600 px-8 text-center text-white opacity-0 transition-opacity duration-700">
                <h1 class="text-2xl sm:text-3xl font-bold">Have something to sell?</h1>
                <p class="mt-3 text-amber-100">Open your own store and reach thousands of shoppers.</p>
                <a href="{{ route('vendor.register') }}" class="mt-6 inline-flex items-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-amber-600 hover:bg-amber-50">
                    Become a vendor
                </a>
            </div>
        </div>

        <div class="absolute inset-x-0 bottom-3 flex items-center justify-center gap-2">
            <button type="button" class="hero-dot h-2 w-6 rounded-full bg-white transition-all" data-slide="0" aria-label="Slide 1"></button>
            <button type="button" class="hero-dot h-2 w-2 rounded-full bg-white/50 transition-all" data-slide="1" aria-label="Slide 2"></button>
            <button type="button" class="hero-dot h-2 w-2 rounded-full bg-white/50 transition-all" data-slide="2" aria-label="Slide 3"></button>
        </div>
    </section>

    <script>
        (function () {
            const slides = document.querySelectorAll('#hero-slider .hero-slide');
            const dots = document.querySelectorAll('#hero-slider .hero-dot');
            let current = 0;
            let timer;

            function show(index) {
                slides.forEach((slide, i) => {
                    slide.style.opacity = i === index ? '1' : '0';
                });
                dots.forEach((dot, i) => {
                    dot.classList.toggle('w-6', i === index);
                    dot.classList.toggle('bg-white', i === index);
                    dot.classList.toggle('w-2', i !== index);
                    dot.classList.toggle('bg-white/50', i !== index);
                });
                current = index;
            }

            function next() {
                show((current + 1) % slides.length);
            }

            function restart() {
                clearInterval(timer);
                timer = setInterval(next, 5000);
            }

            dots.forEach((dot) => {
                dot.addEventListener('click', () => {
                    show(parseInt(dot.dataset.slide, 10));
                    restart();
                });
            });

            if (slides.length > 1) {
                restart();
            }
        })();
    </script>

    {{-- Value props --}}
    <section class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
            <span class="text-2xl">🏬</span>
            <span class="text-sm font-medium text-gray-700">Hundreds of independent stores</span>
        </div>
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
            <span class="text-2xl">🔒</span>
            <span class="text-sm font-medium text-gray-700">Secure checkout</span>
        </div>
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
            <span class="text-2xl">📦</span>
            <span class="text-sm font-medium text-gray-700">Order tracking</span>
        </div>
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
            <span class="text-2xl">🛍️</span>
            <span class="text-sm font-medium text-gray-700">Shop thousands of products</span>
        </div>
    </section>

    @if ($categories->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Shop by category</h2>
                <a href="{{ route('categories.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="mt-4 grid grid-cols-3 sm:grid-cols-5 gap-4">
                @foreach ($categories as $category)
                    @php $categoryImage = $category->getFirstMediaUrl('image'); @endphp
                    <a href="{{ route('categories.show', $category) }}" class="group text-center">
                        <div class="aspect-square overflow-hidden rounded-full border border-gray-200 bg-gradient-to-br from-indigo-100 to-indigo-50 flex items-center justify-center group-hover:shadow-md transition-shadow">
                            @if ($categoryImage)
                                <img src="{{ $categoryImage }}" alt="{{ $category->name }}" class="h-full w-full object-cover">
                            @else
                                <span class="text-2xl font-semibold text-indigo-400">{{ Str::substr($category->name, 0, 1) }}</span>
                            @endif
                        </div>
                        <span class="mt-2 block text-xs sm:text-sm font-medium text-gray-900 truncate">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($featuredProducts->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Featured products</h2>
                <a href="{{ route('shop.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="mt-4 flex gap-4 overflow-x-auto pb-2 snap-x snap-mandatory">
                @foreach ($featuredProducts as $product)
                    <div class="w-40 sm:w-48 flex-none snap-start">
                        @include('storefront.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($newArrivals->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">New arrivals</h2>
                <a href="{{ route('shop.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="mt-4 flex gap-4 overflow-x-auto pb-2 snap-x snap-mandatory">
                @foreach ($newArrivals as $product)
                    <div class="w-40 sm:w-48 flex-none snap-start">
                        @include('storefront.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($brands->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Shop by brand</h2>
                <a href="{{ route('brands.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="mt-4 flex gap-4 overflow-x-auto pb-2">
                @foreach ($brands as $brand)
                    @php $brandLogo = $brand->getFirstMediaUrl('logo'); @endphp
                    <a href="{{ route('brands.show', $brand) }}" class="flex-none w-32 rounded-lg border border-gray-200 bg-white px-3 py-4 text-center hover:shadow-md transition-shadow flex flex-col items-center justify-center gap-2">
                        @if ($brandLogo)
                            <img src="{{ $brandLogo }}" alt="{{ $brand->name }}" class="h-10 object-contain">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-500">{{ Str::substr($brand->name, 0, 1) }}</span>
                        @endif
                        <span class="text-xs font-medium text-gray-900 truncate w-full">{{ $brand->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($stores->isNotEmpty())
        <section class="mt-12">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Featured stores</h2>
                <a href="{{ route('stores.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($stores as $store)
                    <a href="{{ route('stores.show', $store->slug) }}" class="rounded-lg border border-gray-200 bg-white px-4 py-6 text-center hover:shadow-md transition-shadow">
                        <span class="text-sm font-medium text-gray-900">{{ $store->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($categories->isEmpty() && $featuredProducts->isEmpty() && $newArrivals->isEmpty() && $brands->isEmpty() && $stores->isEmpty())
        <section class="mt-12 rounded-lg border border-dashed border-gray-300 bg-white px-8 py-16 text-center">
            <p class="text-gray-500">The catalog is being stocked — check back soon, or <a href="{{ route('vendor.register') }}" class="text-indigo-600 hover:underline">become a vendor</a> to be among the first to sell here.</p>
        </section>
    @endif
@endsection
