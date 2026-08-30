@extends('layouts.storefront')

@section('title', 'Terms of Service')

@section('content')
    @php($page = config('static_pages.terms'))
    <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8 prose prose-sm">
        <h1 class="text-2xl font-bold text-gray-900">{{ $page['title'] }}</h1>
        <p class="text-sm text-gray-500">Last updated: {{ now()->format('F Y') }}</p>

        @foreach ($page['sections'] as $section)
            <h2 class="mt-6 text-lg font-semibold text-gray-900">{{ str_replace(':app_name', config('app.name'), $section['heading']) }}</h2>
            @if ($loop->last)
                <p class="mt-2 text-sm text-gray-700">Questions about these terms can be sent through our <a href="{{ route('pages.contact') }}" class="text-brand-orange hover:underline">contact page</a>.</p>
            @else
                <p class="mt-2 text-sm text-gray-700">{{ str_replace(':app_name', config('app.name'), $section['body']) }}</p>
            @endif
        @endforeach
    </div>
@endsection
