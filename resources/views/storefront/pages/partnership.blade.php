@extends('layouts.storefront')

@section('title', 'Partnership')

@section('content')
    @php($page = config('static_pages.partnership'))
    <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8">
        <h1 class="text-2xl font-bold text-gray-900">{{ str_replace(':app_name', config('app.name'), $page['title']) }}</h1>

        <div class="mt-6 space-y-4 text-sm text-gray-700">
            @foreach ($page['sections'] as $section)
                <p>{{ str_replace(':app_name', config('app.name'), $section['body']) }}</p>
            @endforeach
            <p>For any other partnership or business inquiry, reach out through our <a href="{{ route('pages.contact') }}" class="text-brand-orange hover:underline">contact page</a> and let us know what you have in mind.</p>
        </div>
    </div>
@endsection
