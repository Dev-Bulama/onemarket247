@extends('layouts.storefront')

@section('title', 'Home')

@php
    $heroSlides = \App\Models\HeroSlide::query()->active()->orderBy('sort_order')->get()->filter(fn ($slide) => $slide->imageUrl())->values();
    $deliveryLocationName = ($deliveryLocation['city'] ?? $deliveryLocation['state'] ?? $deliveryLocation['country'] ?? null)?->name;
@endphp

@section('content')
    <div class="flex flex-col md:flex-row gap-6">
        @include('storefront.partials.category-sidebar')

        <div class="flex-1 min-w-0">
            <section class="relative overflow-hidden rounded-xl border border-line bg-white shadow-sm">
                <div class="grid grid-cols-1 lg:grid-cols-2 items-center gap-6 p-6 sm:p-10">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-ink">
                            Everything You Need,<br>
                            <span class="text-brand-orange">One Market, Anytime.</span>
                        </h1>
                        <p class="mt-4 text-body max-w-md">
                            Shop from verified nearby vendors and enjoy quality products, great prices and fast delivery to your doorstep.
                        </p>
                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 rounded-md bg-brand-orange px-6 py-3 text-sm font-semibold text-white hover:bg-brand-orange2">
                                Start Shopping
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                            <a href="{{ route('vendor.register') }}" class="inline-flex items-center gap-2 rounded-md bg-brand-green px-6 py-3 text-sm font-semibold text-white hover:bg-brand-green2">
                                Sell on OneMarket
                                <i class="fa-solid fa-store" aria-hidden="true"></i>
                            </a>
                        </div>

                        <a href="{{ route('shop.index') }}" class="mt-6 flex items-center gap-3 rounded-lg bg-surface px-4 py-3 hover:bg-line/60 transition-colors">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-brand-green/10 text-brand-green">
                                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                            </span>
                            <span class="text-sm">
                                <span class="block font-semibold text-ink">Complete order available nearby</span>
                                <span class="block text-xs text-muted">
                                    @if ($deliveryLocationName)
                                        See products ready to deliver to {{ $deliveryLocationName }}.
                                    @else
                                        See products ready to deliver in your area.
                                    @endif
                                </span>
                            </span>
                            <i class="fa-solid fa-chevron-right ml-auto text-muted" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div id="hero-slider" class="relative aspect-[4/3] rounded-xl overflow-hidden bg-brand-green/10" data-interval="5000">
                        @forelse ($heroSlides as $index => $slide)
                            <img
                                src="{{ $slide->imageUrl() }}"
                                alt="OneMarket 24/7 hero photo {{ $index + 1 }}"
                                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                data-slide-index="{{ $index }}"
                                class="hero-slide absolute inset-0 h-full w-full object-cover transition-opacity duration-700 ease-in-out {{ $index === 0 ? 'opacity-100' : 'opacity-0' }}"
                            >
                        @empty
                            <div class="h-full w-full flex items-center justify-center text-brand-green">
                                <i class="fa-solid fa-bag-shopping text-6xl" aria-hidden="true"></i>
                            </div>
                        @endforelse

                        @if ($heroSlides->count() > 1)
                            <div class="absolute inset-x-0 bottom-3 z-10 flex items-center justify-center gap-1.5" role="tablist" aria-label="Hero photo slides">
                                @foreach ($heroSlides as $index => $slide)
                                    <button
                                        type="button"
                                        class="hero-slide-dot h-2 w-2 rounded-full transition-colors {{ $index === 0 ? 'bg-white' : 'bg-white/50' }}"
                                        data-slide-index="{{ $index }}"
                                        role="tab"
                                        aria-label="Show hero photo {{ $index + 1 }}"
                                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                    ></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            @if ($heroSlides->count() > 1)
                <script>
                    (function () {
                        const root = document.getElementById('hero-slider');
                        if (! root) return;

                        const slides = Array.from(root.querySelectorAll('.hero-slide'));
                        const dots = Array.from(root.querySelectorAll('.hero-slide-dot'));
                        if (slides.length < 2) return;

                        const intervalMs = parseInt(root.dataset.interval, 10) || 5000;
                        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        let current = 0;
                        let timer = null;

                        function show(index) {
                            slides[current].classList.replace('opacity-100', 'opacity-0');
                            dots[current]?.classList.replace('bg-white', 'bg-white/50');
                            dots[current]?.setAttribute('aria-selected', 'false');

                            current = (index + slides.length) % slides.length;

                            slides[current].classList.replace('opacity-0', 'opacity-100');
                            dots[current]?.classList.replace('bg-white/50', 'bg-white');
                            dots[current]?.setAttribute('aria-selected', 'true');
                        }

                        function next() {
                            show(current + 1);
                        }

                        function start() {
                            if (prefersReducedMotion) return;
                            stop();
                            timer = setInterval(next, intervalMs);
                        }

                        function stop() {
                            if (timer) clearInterval(timer);
                            timer = null;
                        }

                        dots.forEach((dot) => {
                            dot.addEventListener('click', () => {
                                show(parseInt(dot.dataset.slideIndex, 10));
                                start();
                            });
                        });

                        root.addEventListener('mouseenter', stop);
                        root.addEventListener('mouseleave', start);
                        root.addEventListener('focusin', stop);
                        root.addEventListener('focusout', start);

                        start();
                    })();
                </script>
            @endif

            {{-- Trust benefits --}}
            <section class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="flex items-center gap-3 rounded-xl border border-line bg-white px-4 py-3 shadow-sm">
                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-brand-green/10 text-brand-green"><i class="fa-solid fa-shield-check" aria-hidden="true"></i></span>
                    <span class="text-sm">
                        <span class="block font-semibold text-ink">Verified Vendors</span>
                        <span class="block text-xs text-muted">Trusted sellers you can rely on</span>
                    </span>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-line bg-white px-4 py-3 shadow-sm">
                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-brand-green/10 text-brand-green"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                    <span class="text-sm">
                        <span class="block font-semibold text-ink">Secure Payments</span>
                        <span class="block text-xs text-muted">Your payments are 100% safe</span>
                    </span>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-line bg-white px-4 py-3 shadow-sm">
                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-brand-green/10 text-brand-green"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i></span>
                    <span class="text-sm">
                        <span class="block font-semibold text-ink">Fast Delivery</span>
                        <span class="block text-xs text-muted">Quick delivery to your doorstep</span>
                    </span>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-line bg-white px-4 py-3 shadow-sm">
                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-brand-green/10 text-brand-green"><i class="fa-solid fa-headset" aria-hidden="true"></i></span>
                    <span class="text-sm">
                        <span class="block font-semibold text-ink">24/7 Support</span>
                        <span class="block text-xs text-muted">We're here anytime you need us</span>
                    </span>
                </div>
            </section>

            {{-- Shop by category --}}
            @if ($navCategories->isNotEmpty())
                <section class="mt-14">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold tracking-tight text-ink">Shop by Category</h2>
                        <a href="{{ route('categories.index') }}" class="text-sm font-medium text-brand-green hover:underline">View all categories</a>
                    </div>
                    <div class="mt-4 flex gap-3 overflow-x-auto pb-2 sm:grid sm:grid-cols-5 lg:grid-cols-10">
                        @foreach ($navCategories->take(10) as $category)
                            <a href="{{ route('categories.show', $category) }}" class="flex-none w-24 sm:w-auto flex flex-col items-center gap-2 rounded-xl border border-line bg-white px-2 py-4 text-center shadow-sm hover:shadow-md hover:border-brand-orange transition-all">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-50 text-brand-orange text-lg">
                                    <i class="{{ $category->displayIcon() }}" aria-hidden="true"></i>
                                </span>
                                <span class="text-xs font-medium text-ink line-clamp-2">{{ $category->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Flash sales --}}
            @if ($flashSaleProducts->isNotEmpty())
                <section class="mt-14" data-flash-sale-ends="{{ $flashSaleEndsAt?->toIso8601String() }}" id="flash-sales">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-orange/10 text-brand-orange"><i class="fa-solid fa-bolt" aria-hidden="true"></i></span>
                            <h2 class="text-xl font-bold tracking-tight text-ink">Flash Sales</h2>
                            <div class="flex items-center gap-1 text-xs font-semibold" id="flash-sale-countdown">
                                <span class="rounded bg-ink px-1.5 py-1 text-white" data-unit="hours">00</span>
                                <span class="text-muted">:</span>
                                <span class="rounded bg-ink px-1.5 py-1 text-white" data-unit="minutes">00</span>
                                <span class="text-muted">:</span>
                                <span class="rounded bg-ink px-1.5 py-1 text-white" data-unit="seconds">00</span>
                            </div>
                        </div>
                        <a href="{{ route('shop.index', ['flash_sale' => 1]) }}" class="text-sm font-medium text-brand-green hover:underline">View all</a>
                    </div>
                    <div class="mt-4 flex gap-4 overflow-x-auto pb-2 snap-x snap-mandatory">
                        @foreach ($flashSaleProducts as $product)
                            <div class="w-40 sm:w-48 flex-none snap-start">
                                @include('storefront.partials.product-card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                </section>

                <script>
                    (function () {
                        const section = document.getElementById('flash-sales');
                        const endsAt = section?.dataset.flashSaleEnds ? new Date(section.dataset.flashSaleEnds).getTime() : null;
                        if (! endsAt) return;

                        const hoursEl = section.querySelector('[data-unit="hours"]');
                        const minutesEl = section.querySelector('[data-unit="minutes"]');
                        const secondsEl = section.querySelector('[data-unit="seconds"]');

                        function tick() {
                            const remaining = Math.max(0, endsAt - Date.now());
                            const hours = Math.floor(remaining / 3600000);
                            const minutes = Math.floor((remaining % 3600000) / 60000);
                            const seconds = Math.floor((remaining % 60000) / 1000);

                            hoursEl.textContent = String(hours).padStart(2, '0');
                            minutesEl.textContent = String(minutes).padStart(2, '0');
                            secondsEl.textContent = String(seconds).padStart(2, '0');

                            if (remaining <= 0) clearInterval(timer);
                        }

                        tick();
                        const timer = setInterval(tick, 1000);
                    })();
                </script>
            @endif

            {{-- Recommended near you --}}
            @if ($recommendedNearYou->isNotEmpty())
                <section class="mt-14">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-ink">Recommended Near You</h2>
                            @if ($deliveryLocationName)
                                <p class="text-xs text-muted">Popular with shoppers around {{ $deliveryLocationName }}.</p>
                            @endif
                        </div>
                        <a href="{{ route('shop.index') }}" class="text-sm font-medium text-brand-green hover:underline">View all</a>
                    </div>
                    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                        @foreach ($recommendedNearYou as $product)
                            @include('storefront.partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($bestSellers->isNotEmpty())
                <section class="mt-14">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-ink">Best sellers</h2>
                            <p class="text-xs text-muted">Ranked by real units sold across the marketplace.</p>
                        </div>
                        <a href="{{ route('shop.index') }}" class="text-sm font-medium text-brand-green hover:underline">View all</a>
                    </div>
                    <div class="mt-4 flex gap-4 overflow-x-auto pb-2 snap-x snap-mandatory">
                        @foreach ($bestSellers as $product)
                            <div class="w-40 sm:w-48 flex-none snap-start">
                                @include('storefront.partials.product-card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($trending->isNotEmpty())
                <section class="mt-14">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold tracking-tight text-ink">Trending products</h2>
                        <a href="{{ route('shop.index') }}" class="text-sm font-medium text-brand-green hover:underline">View all</a>
                    </div>
                    <div class="mt-4 flex gap-4 overflow-x-auto pb-2 snap-x snap-mandatory">
                        @foreach ($trending as $product)
                            <div class="w-40 sm:w-48 flex-none snap-start">
                                @include('storefront.partials.product-card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($featuredProducts->isNotEmpty())
                <section class="mt-14">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold tracking-tight text-ink">Featured products</h2>
                        <a href="{{ route('shop.index') }}" class="text-sm font-medium text-brand-green hover:underline">View all</a>
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

            <section class="mt-10 rounded-lg bg-orange-50 border border-orange-100 px-6 py-4 text-center">
                <p class="text-sm text-orange-900">
                    Have questions about an order or a bulk request?
                    <a href="{{ route('pages.contact') }}" class="font-semibold text-brand-orange hover:underline">Contact us</a>
                    — {{ config('app.name') }}
                </p>
            </section>

            @if ($newArrivals->isNotEmpty())
                <section class="mt-14">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold tracking-tight text-ink">New arrivals</h2>
                        <a href="{{ route('shop.index') }}" class="text-sm font-medium text-brand-green hover:underline">View all</a>
                    </div>
                    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach ($newArrivals as $product)
                            @include('storefront.partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($brands->isNotEmpty())
                <section class="mt-14">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold tracking-tight text-ink">Shop by brand</h2>
                        <a href="{{ route('brands.index') }}" class="text-sm font-medium text-brand-green hover:underline">View all</a>
                    </div>
                    <div class="mt-4 flex gap-4 overflow-x-auto pb-2">
                        @foreach ($brands as $brand)
                            @php $brandLogo = $brand->getFirstMediaUrl('logo'); @endphp
                            <a href="{{ route('brands.show', $brand) }}" class="flex-none w-32 rounded-xl border border-line bg-white px-3 py-4 text-center shadow-sm hover:shadow-md transition-shadow flex flex-col items-center justify-center gap-2">
                                @if ($brandLogo)
                                    <img src="{{ $brandLogo }}" alt="{{ $brand->name }}" class="h-10 object-contain">
                                @else
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-surface text-sm font-semibold text-body">{{ Str::substr($brand->name, 0, 1) }}</span>
                                @endif
                                <span class="text-xs font-medium text-ink truncate w-full">{{ $brand->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($stores->isNotEmpty())
                <section class="mt-14">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold tracking-tight text-ink">Featured stores</h2>
                        <a href="{{ route('stores.index') }}" class="text-sm font-medium text-brand-green hover:underline">View all</a>
                    </div>
                    <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ($stores as $store)
                            <a href="{{ route('stores.show', $store->slug) }}" class="rounded-xl border border-line bg-white px-4 py-6 text-center shadow-sm hover:shadow-md transition-shadow">
                                <span class="text-sm font-medium text-ink">{{ $store->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($bestSellers->isEmpty() && $trending->isEmpty() && $featuredProducts->isEmpty() && $newArrivals->isEmpty() && $brands->isEmpty() && $stores->isEmpty())
                <section class="mt-10 rounded-lg border border-dashed border-line bg-white px-8 py-16 text-center">
                    <p class="text-body">The catalog is being stocked — check back soon, or <a href="{{ route('vendor.register') }}" class="text-brand-orange hover:underline">become a vendor</a> to be among the first to sell here.</p>
                </section>
            @endif
        </div>
    </div>
@endsection
