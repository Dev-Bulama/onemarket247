@extends('layouts.storefront')

@section('title', 'Categories')

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Categories</h1>

    @if ($categories->isEmpty())
        <p class="text-sm text-gray-600">No categories yet.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($categories as $category)
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <a href="{{ route('categories.show', $category) }}" class="flex items-center gap-3 text-base font-semibold text-gray-900 hover:text-brand-orange">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-orange-50 text-brand-orange flex-none">
                            <i class="{{ $category->displayIcon() }}" aria-hidden="true"></i>
                        </span>
                        {{ $category->name }}
                    </a>

                    @if ($category->children->isNotEmpty())
                        <ul class="mt-2 space-y-1">
                            @foreach ($category->children as $child)
                                <li>
                                    <a href="{{ route('categories.show-subcategory', [$category, $child]) }}" class="text-sm text-gray-600 hover:text-brand-orange">
                                        {{ $child->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
