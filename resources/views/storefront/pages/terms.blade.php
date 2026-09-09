@extends('layouts.storefront')

@section('title', $page->title)

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8 prose prose-sm">
        <h1 class="text-2xl font-bold text-gray-900">{{ $page->title }}</h1>
        <p class="text-sm text-gray-500">Last updated: {{ $page->updated_at->format('F Y') }}</p>

        {!! str_replace(':app_name', config('app.name'), $page->body) !!}

        <p class="mt-2 text-sm text-gray-700">Questions about these terms can be sent through our <a href="{{ route('pages.contact') }}" class="text-brand-orange hover:underline">contact page</a>.</p>
    </div>
@endsection
