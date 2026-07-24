<nav class="md:hidden fixed inset-x-0 bottom-0 z-40 bg-white border-t border-line pb-[env(safe-area-inset-bottom)]" aria-label="Mobile navigation">
    <div class="grid grid-cols-5 text-center text-[11px] text-muted">
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-0.5 py-2 {{ request()->routeIs('home') ? 'text-brand-orange' : '' }}">
            <i class="fa-solid fa-house text-base" aria-hidden="true"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('categories.index') }}" class="flex flex-col items-center gap-0.5 py-2 {{ request()->routeIs('categories.*') ? 'text-brand-orange' : '' }}">
            <i class="fa-solid fa-bars text-base" aria-hidden="true"></i>
            <span>Categories</span>
        </a>
        <a href="{{ route('search.index') }}" class="flex flex-col items-center gap-0.5 py-2 {{ request()->routeIs('search.*') ? 'text-brand-orange' : '' }}">
            <i class="fa-solid fa-magnifying-glass text-base" aria-hidden="true"></i>
            <span>Search</span>
        </a>
        <a href="{{ route('cart.index') }}" class="relative flex flex-col items-center gap-0.5 py-2 {{ request()->routeIs('cart.*') ? 'text-brand-orange' : '' }}">
            <i class="fa-solid fa-cart-shopping text-base" aria-hidden="true"></i>
            <span>Cart</span>
            @if (($cartItemCount ?? 0) > 0)
                <span class="absolute top-1 right-1/4 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-orange px-1 text-[9px] font-semibold text-white">{{ $cartItemCount }}</span>
            @endif
        </a>
        @auth
            <a href="{{ route('account.dashboard') }}" class="flex flex-col items-center gap-0.5 py-2 {{ request()->routeIs('account.*') ? 'text-brand-orange' : '' }}">
                <i class="fa-solid fa-user text-base" aria-hidden="true"></i>
                <span>Account</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="flex flex-col items-center gap-0.5 py-2">
                <i class="fa-solid fa-user text-base" aria-hidden="true"></i>
                <span>Account</span>
            </a>
        @endauth
    </div>
</nav>
