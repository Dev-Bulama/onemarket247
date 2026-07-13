<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">
<header class="bg-white border-b border-gray-200">
    <div class="mx-auto max-w-6xl px-4 py-4 flex flex-wrap items-center gap-4">
        <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900 shrink-0">{{ config('app.name') }}</a>

        <nav class="flex items-center gap-4 text-sm text-gray-600 order-3 w-full sm:order-none sm:w-auto">
            <a href="{{ route('shop.index') }}" class="hover:text-gray-900">Shop</a>
            <a href="{{ route('categories.index') }}" class="hover:text-gray-900">Categories</a>
            <a href="{{ route('brands.index') }}" class="hover:text-gray-900">Brands</a>
            <a href="{{ route('stores.index') }}" class="hover:text-gray-900">Stores</a>
        </nav>

        <form action="{{ route('search.index') }}" method="GET" class="flex-1 min-w-[180px] sm:ml-auto">
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

        <div class="flex items-center gap-4 text-sm text-gray-600">
            @auth
                <a href="{{ route('account.dashboard') }}" class="hover:text-gray-900">Account</a>
            @else
                <a href="{{ route('login') }}" class="hover:text-gray-900">Log in</a>
            @endauth
        </div>
    </div>
</header>

<main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
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

<footer class="bg-white border-t border-gray-200">
    <div class="mx-auto max-w-6xl px-4 py-8 flex flex-wrap items-center justify-between gap-4 text-sm text-gray-500">
        <p>&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
        <nav class="flex items-center gap-4">
            <a href="{{ route('pages.contact') }}" class="hover:text-gray-700">Contact</a>
            <a href="{{ route('pages.faq') }}" class="hover:text-gray-700">FAQ</a>
            <a href="{{ route('pages.terms') }}" class="hover:text-gray-700">Terms</a>
            <a href="{{ route('pages.privacy') }}" class="hover:text-gray-700">Privacy Policy</a>
        </nav>
    </div>
</footer>
</body>
</html>
