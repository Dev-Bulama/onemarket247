<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($currentLanguage ?? null)?->isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">

@if (($announcementText ?? null))
    <div class="bg-indigo-600 text-white text-center text-sm py-2 px-4">
        {{ $announcementText }}
    </div>
@endif

<div class="bg-gray-50 border-b border-gray-200 text-xs text-gray-600">
    <div class="mx-auto max-w-6xl px-4 py-1.5 flex flex-wrap items-center justify-between gap-2">
        <nav class="flex items-center gap-4">
            <a href="{{ route('pages.about-us') }}" class="hover:text-gray-900">About Us</a>
            @auth
                <a href="{{ route('account.dashboard') }}" class="hover:text-gray-900">My Account</a>
            @else
                <a href="{{ route('login') }}" class="hover:text-gray-900">My Account</a>
            @endauth
            <a href="{{ route('account.wishlist.index') }}" class="hover:text-gray-900">Wishlist</a>
            <a href="{{ route('account.orders.index') }}" class="hover:text-gray-900">Order Tracking</a>
        </nav>
        <div class="flex items-center gap-4">
            <span class="hidden sm:inline"><i class="fa-solid fa-lock" aria-hidden="true"></i> Secure checkout</span>
            @if ($switchableLanguages->count() > 1)
                <form method="POST" action="{{ route('locale.switch', ($currentLanguage ?? null)?->code ?? 'en') }}">
                    @csrf
                    <select name="code" onchange="this.form.action = this.form.action.replace(/[^\/]+$/, this.value); this.form.submit()" class="rounded-md border-gray-300 text-xs py-0.5">
                        @foreach ($switchableLanguages as $language)
                            <option value="{{ $language->code }}" @selected(($currentLanguage ?? null)?->code === $language->code)>{{ $language->native_name ?? $language->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            @if ($switchableCurrencies->count() > 1)
                <form method="POST" action="{{ route('currency.switch', ($displayCurrency ?? null)?->code ?? 'USD') }}">
                    @csrf
                    <select name="code" onchange="this.form.action = this.form.action.replace(/[^\/]+$/, this.value); this.form.submit()" class="rounded-md border-gray-300 text-xs py-0.5">
                        @foreach ($switchableCurrencies as $currency)
                            <option value="{{ $currency->code }}" @selected(($displayCurrency ?? null)?->code === $currency->code)>{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>
</div>

<header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-gray-200 shadow-sm">
    <div class="mx-auto max-w-6xl px-4 py-4 flex flex-wrap items-center gap-4">
        <a href="{{ route('home') }}" class="text-2xl font-extrabold tracking-tight text-gray-900 shrink-0">{{ config('app.name') }}</a>

        <details class="relative order-3 sm:order-none">
            <summary class="list-none cursor-pointer flex items-center gap-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:border-indigo-400">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                @if (($deliveryLocation['country'] ?? null))
                    <span>Delivering to: {{ ($deliveryLocation['city'] ?? $deliveryLocation['state'] ?? $deliveryLocation['country'])->name }}</span>
                    @if ($deliveryLocation['deliverable'] === false)
                        <span class="text-red-600">(not deliverable)</span>
                    @endif
                @else
                    <span>Select Location</span>
                @endif
            </summary>
            <form method="POST" action="{{ route('location.switch') }}" class="absolute z-20 mt-2 w-64 rounded-md border border-gray-200 bg-white p-4 shadow-lg space-y-3">
                @csrf
                <div>
                    <label for="location_country_id" class="block text-xs font-medium text-gray-700">Country</label>
                    <select name="country_id" id="location_country_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">—</option>
                        @foreach ($allCountries as $country)
                            <option value="{{ $country->id }}" @selected(($deliveryLocation['country']->id ?? null) == $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="location_state_id" class="block text-xs font-medium text-gray-700">State</label>
                    <select name="state_id" id="location_state_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm"></select>
                </div>
                <div>
                    <label for="location_city_id" class="block text-xs font-medium text-gray-700">City</label>
                    <select name="city_id" id="location_city_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm"></select>
                </div>
                <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-500">Save location</button>
            </form>
        </details>

        <form action="{{ route('search.index') }}" method="GET" class="flex-1 min-w-[180px] order-4 sm:order-none">
            <label for="storefront-search" class="sr-only">Search products</label>
            <input
                id="storefront-search"
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search products…"
                class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
        </form>

        <div class="flex items-center gap-4 text-sm text-gray-600 ml-auto sm:ml-0">
            @auth
                <a href="{{ route('account.dashboard') }}" class="hover:text-gray-900" title="Account"><i class="fa-solid fa-user" aria-hidden="true"></i></a>
            @else
                <a href="{{ route('login') }}" class="hover:text-gray-900" title="Log in"><i class="fa-solid fa-user" aria-hidden="true"></i></a>
            @endauth
            <a href="{{ route('cart.index') }}" class="hover:text-gray-900" title="Cart">
                <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                @if (($cartItemCount ?? 0) > 0)
                    <span class="ml-1 inline-flex items-center justify-center rounded-full bg-indigo-600 px-1.5 py-0.5 text-xs font-medium text-white">{{ $cartItemCount }}</span>
                @endif
            </a>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 pb-3 flex flex-wrap items-center gap-4">
        <details class="relative">
            <summary class="list-none cursor-pointer flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                <span><i class="fa-solid fa-bars" aria-hidden="true"></i> All Categories</span>
                <span class="text-xs bg-white/20 rounded px-1.5 py-0.5">{{ $totalProductCount }} products</span>
            </summary>
            <div class="absolute z-20 mt-2 w-64 rounded-md border border-gray-200 bg-white shadow-lg py-1">
                @forelse ($navCategories as $category)
                    <a href="{{ route('categories.show', $category) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-600">
                        <i class="{{ $category->displayIcon() }} text-indigo-500 w-4 text-center" aria-hidden="true"></i>
                        <span>{{ $category->name }}</span>
                    </a>
                @empty
                    <p class="px-4 py-2 text-sm text-gray-400">No categories yet</p>
                @endforelse
            </div>
        </details>

        <nav class="flex items-center gap-4 text-sm font-medium text-gray-700 flex-wrap">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 {{ request()->routeIs('home') ? 'text-indigo-600' : '' }}">Home</a>
            <a href="{{ route('shop.index') }}" class="hover:text-indigo-600 {{ request()->routeIs('shop.*') ? 'text-indigo-600' : '' }}">Shop</a>
            <a href="{{ route('pages.about-us') }}" class="hover:text-indigo-600">About Us</a>
            <a href="{{ route('blog.index') }}" class="hover:text-indigo-600 {{ request()->routeIs('blog.*') ? 'text-indigo-600' : '' }}">Blog</a>
            <a href="{{ route('pages.contact') }}" class="hover:text-indigo-600">Contact</a>
            <a href="{{ route('pages.partnership') }}" class="hover:text-indigo-600">Partnership</a>
            <a href="{{ route('vendor.register') }}" class="hover:text-indigo-600">Be a Vendor</a>
        </nav>
    </div>
</header>

<main class="mx-auto w-full max-w-6xl flex-1 px-4 py-10">
    @if (session('status'))
        <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
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

<footer class="bg-gray-50 border-t border-gray-200 mt-auto">
    <div class="mx-auto max-w-6xl px-4 py-12 grid grid-cols-1 sm:grid-cols-3 gap-8 text-sm text-gray-500">
        <div>
            <p class="text-lg font-extrabold tracking-tight text-gray-900">{{ config('app.name') }}</p>
            <p class="mt-2 text-gray-500 max-w-xs">A multi-vendor marketplace connecting shoppers with hundreds of independent stores.</p>
            <div class="mt-4 flex items-center gap-2 text-xs text-gray-400">
                <i class="fa-solid fa-shield-halved text-indigo-500" aria-hidden="true"></i>
                <span>Secure payments &amp; buyer protection</span>
            </div>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Company</p>
            <nav class="mt-3 flex flex-col gap-2">
                <a href="{{ route('pages.about-us') }}" class="hover:text-indigo-600">About Us</a>
                <a href="{{ route('blog.index') }}" class="hover:text-indigo-600">Blog</a>
                <a href="{{ route('pages.partnership') }}" class="hover:text-indigo-600">Partnership</a>
                <a href="{{ route('vendor.register') }}" class="hover:text-indigo-600">Become a Vendor</a>
            </nav>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Support</p>
            <nav class="mt-3 flex flex-col gap-2">
                <a href="{{ route('pages.contact') }}" class="hover:text-indigo-600">Contact</a>
                <a href="{{ route('pages.faq') }}" class="hover:text-indigo-600">FAQ</a>
                <a href="{{ route('pages.terms') }}" class="hover:text-indigo-600">Terms</a>
                <a href="{{ route('pages.privacy') }}" class="hover:text-indigo-600">Privacy Policy</a>
            </nav>
        </div>
    </div>
    <div class="border-t border-gray-200">
        <p class="mx-auto max-w-6xl px-4 py-4 text-xs text-gray-400">&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</footer>

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
</body>
</html>
