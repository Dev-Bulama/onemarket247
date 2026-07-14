@extends('layouts.storefront')

@section('title', 'Blog')

@section('content')
    <h1 class="text-2xl font-bold text-gray-900">Blog</h1>

    @if ($posts->isEmpty())
        <p class="mt-6 text-gray-500">No posts yet — check back soon.</p>
    @else
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($posts as $post)
                @php $cover = $post->getFirstMediaUrl('cover'); @endphp
                <a href="{{ route('blog.show', $post) }}" class="block bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="aspect-video bg-gray-100 flex items-center justify-center overflow-hidden">
                        @if ($cover)
                            <img src="{{ $cover }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
                        @else
                            <span class="text-gray-300 text-sm">No image</span>
                        @endif
                    </div>
                    <div class="p-4">
                        <p class="text-xs text-gray-500">{{ $post->published_at->format('M j, Y') }}</p>
                        <h2 class="mt-1 font-semibold text-gray-900">{{ $post->title }}</h2>
                        <p class="mt-2 text-sm text-gray-600 line-clamp-3">{{ $post->displayExcerpt() }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $posts->onEachSide(1)->links() }}
        </div>
    @endif
@endsection
