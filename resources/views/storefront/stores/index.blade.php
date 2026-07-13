@extends('layouts.storefront')

@section('title', 'Stores')

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Stores</h1>

    <form method="GET" class="mb-6 max-w-sm">
        <label for="store-search" class="sr-only">Search stores</label>
        <input
            id="store-search"
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search stores…"
            onchange="this.form.submit()"
            class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
    </form>

    @if ($stores->isEmpty())
        <p class="text-sm text-gray-600">No stores match your search.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($stores as $store)
                <a href="{{ route('stores.show', $store->slug) }}" class="block rounded-lg border border-gray-200 bg-white p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-gray-900">{{ $store->name }}</span>
                        @if ($store->is_verified)
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Verified</span>
                        @endif
                    </div>
                    @if ($store->description)
                        <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ $store->description }}</p>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $stores->onEachSide(1)->links() }}
        </div>
    @endif
@endsection
