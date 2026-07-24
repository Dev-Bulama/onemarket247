@extends('layouts.storefront')

@section('title', 'Partnership')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8">
        <h1 class="text-2xl font-bold text-gray-900">Partner with {{ config('app.name') }}</h1>

        <div class="mt-6 space-y-4 text-sm text-gray-700">
            <p>We work with vendors, logistics providers, and other businesses that want to reach our customer base or support how we operate.</p>
            <p>If you run a store and want to sell here, start with our <a href="{{ route('vendor.register') }}" class="text-brand-orange hover:underline">vendor registration page</a>.</p>
            <p>For any other partnership or business inquiry, reach out through our <a href="{{ route('pages.contact') }}" class="text-brand-orange hover:underline">contact page</a> and let us know what you have in mind.</p>
        </div>
    </div>
@endsection
