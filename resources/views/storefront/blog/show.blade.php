@extends('layouts.storefront')

@section('title', $post->title)

@section('content')
    @php $cover = $post->getFirstMediaUrl('cover'); @endphp

    <article class="max-w-3xl mx-auto">
        <p class="text-sm text-gray-500">{{ $post->published_at->format('F j, Y') }}</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $post->title }}</h1>

        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $post->title }}" class="mt-6 w-full rounded-lg object-cover">
        @endif

        <div class="mt-6 prose prose-sm max-w-none text-gray-700">
            {!! nl2br(e($post->body)) !!}
        </div>
    </article>

    @if ($recent->isNotEmpty())
        <div class="mt-12 max-w-3xl mx-auto">
            <h2 class="text-lg font-semibold text-gray-900">More posts</h2>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($recent as $item)
                    <a href="{{ route('blog.show', $item) }}" class="block rounded-lg border border-gray-200 bg-white p-4 hover:shadow-md transition-shadow">
                        <p class="text-xs text-gray-500">{{ $item->published_at->format('M j, Y') }}</p>
                        <p class="mt-1 font-medium text-gray-900">{{ $item->title }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endsection
