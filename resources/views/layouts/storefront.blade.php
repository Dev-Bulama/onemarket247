<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($currentLanguage ?? null)?->isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.tailwind-brand-config')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="font-sans min-h-screen bg-warm flex flex-col pb-14 md:pb-0">

<div class="bg-ink text-white text-xs">
    <div class="mx-auto max-w-6xl px-4 py-2 flex flex-wrap items-center justify-between gap-2">
        <p class="flex items-center gap-2">
            <i class="fa-solid fa-bullhorn text-brand-orange" aria-hidden="true"></i>
            <span>{{ $announcementText ?? 'Free delivery on qualifying orders. Shop now!' }}</span>
            <a href="{{ route('shop.index') }}" class="font-semibold text-brand-orange hover:underline">Shop now!</a>
        </p>
        <nav class="flex items-center gap-4 text-white/80">
            <a href="{{ route('vendor.register') }}" class="hover:text-white">Become a Vendor</a>
            <a href="{{ route('pages.track-order') }}" class="hover:text-white">Track Order</a>
            <a href="{{ route('pages.faq') }}" class="hover:text-white">Help Center</a>
        </nav>
    </div>
</div>

<header class="sticky top-0 z-30 bg-white border-b border-line shadow-sm">
    <div class="mx-auto max-w-6xl px-4 py-3 flex flex-wrap items-center gap-3">
        @include('storefront.partials.logo')

        <details class="relative order-3 sm:order-none">
            <summary class="list-none cursor-pointer flex items-center gap-1 rounded-md border border-line px-3 py-1.5 text-sm text-body hover:border-brand-orange">
                <i class="fa-solid fa-location-dot text-brand-green" aria-hidden="true"></i>
                @if (($deliveryLocation['country'] ?? null))
                    <span>{{ ($deliveryLocation['city'] ?? $deliveryLocation['state'] ?? $deliveryLocation['country'])->name }}</span>
                    @if ($deliveryLocation['deliverable'] === false)
                        <span class="text-red-600">(not deliverable)</span>
                    @endif
                @else
                    <span>Select Location</span>
                @endif
                <i class="fa-solid fa-chevron-down text-[10px] text-muted" aria-hidden="true"></i>
            </summary>
            <form method="POST" action="{{ route('location.switch') }}" class="absolute z-20 mt-2 w-64 rounded-md border border-line bg-white p-4 shadow-lg space-y-3">
                @csrf
                <div>
                    <label for="location_country_id" class="block text-xs font-medium text-body">Country</label>
                    <select name="country_id" id="location_country_id" required class="mt-1 block w-full rounded-md border-line text-sm">
                        <option value="">—</option>
                        @foreach ($allCountries as $country)
                            <option value="{{ $country->id }}" @selected(($deliveryLocation['country']->id ?? null) == $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="location_state_id" class="block text-xs font-medium text-body">State</label>
                    <select name="state_id" id="location_state_id" class="mt-1 block w-full rounded-md border-line text-sm"></select>
                </div>
                <div>
                    <label for="location_city_id" class="block text-xs font-medium text-body">City</label>
                    <select name="city_id" id="location_city_id" class="mt-1 block w-full rounded-md border-line text-sm"></select>
                </div>
                <button type="submit" class="w-full rounded-md bg-brand-green px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-green2">Save location</button>
            </form>
        </details>

        <form action="{{ route('search.index') }}" method="GET" class="flex-1 min-w-[200px] order-4 sm:order-none flex rounded-md border border-line overflow-hidden focus-within:border-brand-orange focus-within:ring-1 focus-within:ring-brand-orange">
            <label for="storefront-search" class="sr-only">Search products, brands and categories</label>
            <input
                id="storefront-search"
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search for products, brands and categories"
                class="flex-1 min-w-0 border-0 px-3 py-2 text-sm focus:outline-none focus:ring-0"
            >
            <label for="storefront-search-category" class="sr-only">Category</label>
            <select id="storefront-search-category" name="category_id" class="hidden sm:block border-0 border-l border-line bg-surface px-2 text-xs text-body focus:outline-none focus:ring-0">
                <option value="">All Categories</option>
                @foreach ($navCategories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="flex items-center justify-center bg-brand-orange px-4 text-white hover:bg-brand-orange2" aria-label="Search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            </button>
        </form>

        <div class="hidden md:flex items-center gap-5 text-xs text-body ml-auto sm:ml-0">
            @auth
                <a href="{{ route('account.dashboard') }}" class="flex flex-col items-center hover:text-brand-orange">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    <span class="mt-0.5">Account</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="flex flex-col items-center hover:text-brand-orange">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    <span class="mt-0.5">Sign in</span>
                </a>
            @endauth
            <a href="{{ route('account.orders.index') }}" class="flex flex-col items-center hover:text-brand-orange">
                <i class="fa-solid fa-box" aria-hidden="true"></i>
                <span class="mt-0.5">Orders</span>
            </a>
            <a href="{{ route('account.wishlist.index') }}" class="flex flex-col items-center hover:text-brand-orange">
                <i class="fa-solid fa-heart" aria-hidden="true"></i>
                <span class="mt-0.5">Wishlist</span>
            </a>
            <a href="{{ route('cart.index') }}" class="relative flex flex-col items-center hover:text-brand-orange">
                <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                <span class="mt-0.5">Cart</span>
                @if (($cartItemCount ?? 0) > 0)
                    <span class="absolute -top-1 -right-2 inline-flex items-center justify-center rounded-full bg-brand-orange px-1.5 py-0.5 text-[10px] font-medium text-white">{{ $cartItemCount }}</span>
                @endif
            </a>
        </div>
        <a href="{{ route('cart.index') }}" class="md:hidden relative ml-auto text-body">
            <i class="fa-solid fa-cart-shopping text-lg" aria-hidden="true"></i>
            @if (($cartItemCount ?? 0) > 0)
                <span class="absolute -top-1 -right-2 inline-flex items-center justify-center rounded-full bg-brand-orange px-1.5 py-0.5 text-[10px] font-medium text-white">{{ $cartItemCount }}</span>
            @endif
        </a>
    </div>

    <div class="border-t border-line bg-white">
        <div class="mx-auto max-w-6xl px-4 flex items-stretch gap-1 overflow-x-auto scrollbar-hide">
            <details class="relative flex-none">
                <summary class="list-none cursor-pointer flex h-full items-center gap-2 px-3 py-2.5 text-sm font-medium text-ink hover:text-brand-orange whitespace-nowrap">
                    <i class="fa-solid fa-bars text-brand-green" aria-hidden="true"></i>
                    All Categories
                    <i class="fa-solid fa-chevron-down text-[10px] text-muted" aria-hidden="true"></i>
                </summary>
                <div class="absolute z-20 mt-0 w-72 rounded-b-md border border-line bg-white shadow-lg py-1 max-h-96 overflow-y-auto">
                    @forelse ($navCategories as $category)
                        <a href="{{ route('categories.show', $category) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-body hover:bg-surface hover:text-brand-orange">
                            <i class="{{ $category->displayIcon() }} text-brand-green w-4 text-center" aria-hidden="true"></i>
                            <span>{{ $category->name }}</span>
                        </a>
                    @empty
                        <p class="px-4 py-2 text-sm text-muted">No categories yet</p>
                    @endforelse
                </div>
            </details>

            @foreach ($navCategories->take(9) as $category)
                <a href="{{ route('categories.show', $category) }}" class="flex-none flex items-center gap-1.5 px-3 py-2.5 text-sm text-body hover:text-brand-orange whitespace-nowrap">
                    <i class="{{ $category->displayIcon() }} text-xs" aria-hidden="true"></i>
                    {{ $category->name }}
                </a>
            @endforeach

            <a href="{{ route('shop.index', ['flash_sale' => 1]) }}" class="flex-none flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium text-brand-orange whitespace-nowrap ml-auto">
                <i class="fa-solid fa-tag" aria-hidden="true"></i>
                Deals
            </a>
        </div>
    </div>
</header>

<main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
    @if (session('status'))
        <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-brand-green2">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="bg-surface border-t border-line mt-auto">
    <div class="mx-auto max-w-6xl px-4 py-12 grid grid-cols-1 sm:grid-cols-4 gap-8 text-sm text-body">
        <div class="sm:col-span-2">
            @include('storefront.partials.logo')
            <p class="mt-3 text-body max-w-xs">Everything you need, one market, anytime — shop from verified nearby vendors with fast delivery to your doorstep.</p>
            <div class="mt-4 flex items-center gap-2 text-xs text-muted">
                <i class="fa-solid fa-shield-halved text-brand-green" aria-hidden="true"></i>
                <span>Secure payments &amp; buyer protection</span>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                @if ($switchableLanguages->count() > 1)
                    <form method="POST" action="{{ route('locale.switch', ($currentLanguage ?? null)?->code ?? 'en') }}">
                        @csrf
                        <select name="code" onchange="this.form.action = this.form.action.replace(/[^\/]+$/, this.value); this.form.submit()" class="rounded-md border-line text-xs py-1">
                            @foreach ($switchableLanguages as $language)
                                <option value="{{ $language->code }}" @selected(($currentLanguage ?? null)?->code === $language->code)>{{ $language->native_name ?? $language->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                @if ($switchableCurrencies->count() > 1)
                    <form method="POST" action="{{ route('currency.switch', ($displayCurrency ?? null)?->code ?? 'NGN') }}">
                        @csrf
                        <select name="code" onchange="this.form.action = this.form.action.replace(/[^\/]+$/, this.value); this.form.submit()" class="rounded-md border-line text-xs py-1">
                            @foreach ($switchableCurrencies as $currency)
                                <option value="{{ $currency->code }}" @selected(($displayCurrency ?? null)?->code === $currency->code)>{{ $currency->code }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Company</p>
            <nav class="mt-3 flex flex-col gap-2">
                <a href="{{ route('pages.about-us') }}" class="hover:text-brand-orange">About Us</a>
                <a href="{{ route('blog.index') }}" class="hover:text-brand-orange">Blog</a>
                <a href="{{ route('pages.partnership') }}" class="hover:text-brand-orange">Partnership</a>
                <a href="{{ route('vendor.register') }}" class="hover:text-brand-orange">Become a Vendor</a>
            </nav>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Support</p>
            <nav class="mt-3 flex flex-col gap-2">
                <a href="{{ route('pages.contact') }}" class="hover:text-brand-orange">Contact</a>
                <a href="{{ route('pages.track-order') }}" class="hover:text-brand-orange">Track Order</a>
                <a href="{{ route('pages.faq') }}" class="hover:text-brand-orange">Help Center / FAQ</a>
                <a href="{{ route('pages.terms') }}" class="hover:text-brand-orange">Terms</a>
                <a href="{{ route('pages.privacy') }}" class="hover:text-brand-orange">Privacy Policy</a>
            </nav>
        </div>
    </div>
    <div class="border-t border-line">
        <p class="mx-auto max-w-6xl px-4 py-4 text-xs text-muted">&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</footer>

@include('storefront.partials.mobile-nav')

<script>
    const headerStates = @json($allStates);
    const headerCities = @json($allCities);

    function populateLocationStates(countryId, selected) {
        const stateSelect = document.getElementById('location_state_id');
        if (! stateSelect) return;
        stateSelect.innerHTML = '<option value="">—</option>';
        headerStates.filter(s => s.country_id == countryId).forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            if (selected && selected == s.id) opt.selected = true;
            stateSelect.appendChild(opt);
        });
        populateLocationCities(stateSelect.value);
    }

    function populateLocationCities(stateId, selected) {
        const citySelect = document.getElementById('location_city_id');
        if (! citySelect) return;
        citySelect.innerHTML = '<option value="">—</option>';
        headerCities.filter(c => c.state_id == stateId).forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            if (selected && selected == c.id) opt.selected = true;
            citySelect.appendChild(opt);
        });
    }

    const locationCountrySelect = document.getElementById('location_country_id');
    if (locationCountrySelect) {
        locationCountrySelect.addEventListener('change', (e) => populateLocationStates(e.target.value));
        document.getElementById('location_state_id').addEventListener('change', (e) => populateLocationCities(e.target.value));

        if (locationCountrySelect.value) {
            populateLocationStates(locationCountrySelect.value, '{{ $deliveryLocation['state']->id ?? '' }}');
            populateLocationCities('{{ $deliveryLocation['state']->id ?? '' }}', '{{ $deliveryLocation['city']->id ?? '' }}');
        }
    }
</script>
@yield('scripts')
</body>
</html>
