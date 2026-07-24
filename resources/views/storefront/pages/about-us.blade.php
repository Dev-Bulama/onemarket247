@extends('layouts.storefront')

@section('title', 'About Us')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8">
        <h1 class="text-2xl font-bold text-gray-900">About {{ config('app.name') }}</h1>

        <div class="mt-6 space-y-4 text-sm text-gray-700">
            <p>{{ config('app.name') }} is a multi-vendor marketplace that brings together independent stores in one place, so customers can shop a wide range of products while every order is fulfilled directly by the vendor who sells it.</p>
            <p>Each vendor manages their own store, catalog, and orders through a dedicated dashboard, while our platform handles the shared essentials: secure checkout, order tracking, and customer support across every store.</p>
            <p>Whether you're here to shop or you have products of your own to sell, we'd love to have you — see our <a href="{{ route('vendor.register') }}" class="text-brand-orange hover:underline">vendor registration page</a> to get started, or <a href="{{ route('pages.contact') }}" class="text-brand-orange hover:underline">contact us</a> with any questions.</p>
        </div>
    </div>
@endsection
