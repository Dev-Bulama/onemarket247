@extends('layouts.storefront')

@section('title', 'About Us')

@section('content')
    @php($page = config('static_pages.about-us'))
    <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8">
        <h1 class="text-2xl font-bold text-gray-900">{{ str_replace(':app_name', config('app.name'), $page['title']) }}</h1>

        <div class="mt-6 space-y-4 text-sm text-gray-700">
            @foreach ($page['sections'] as $section)
                <p>{{ str_replace(':app_name', config('app.name'), $section['body']) }}</p>
            @endforeach
            <p>See our <a href="{{ route('vendor.register') }}" class="text-brand-orange hover:underline">vendor registration page</a> to get started, or <a href="{{ route('pages.contact') }}" class="text-brand-orange hover:underline">contact us</a> with any questions.</p>
        </div>
    </div>
@endsection
