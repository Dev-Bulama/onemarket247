<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($currentLanguage ?? null)?->isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.tailwind-brand-config')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="font-sans min-h-screen bg-warm">
    <nav class="bg-white border-b border-line">
        <div class="max-w-4xl mx-auto px-4 flex items-center justify-between h-14">
            @include('storefront.partials.logo')
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('account.dashboard') }}" class="text-body hover:text-brand-orange">Account</a>
                <a href="{{ route('account.profile.edit') }}" class="text-body hover:text-brand-orange">Profile</a>
                <a href="{{ route('account.orders.index') }}" class="text-body hover:text-brand-orange">Orders</a>
                <a href="{{ route('account.addresses.index') }}" class="text-body hover:text-brand-orange">Addresses</a>
                <a href="{{ route('account.wishlist.index') }}" class="text-body hover:text-brand-orange">Wishlist</a>
                <a href="{{ route('account.compare.index') }}" class="text-body hover:text-brand-orange">Compare</a>
                <a href="{{ route('account.notifications.index') }}" class="text-body hover:text-brand-orange">
                    Messages
                    @php($unreadCount = auth()->user()?->unreadNotifications()->count())
                    @if ($unreadCount)
                        <span class="ml-1 inline-flex items-center justify-center rounded-full bg-brand-orange px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $unreadCount }}</span>
                    @endif
                </a>
                <a href="{{ route('account.security') }}" class="text-body hover:text-brand-orange">Security</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-body hover:text-brand-orange">Log out</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-8">
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
</body>
</html>
