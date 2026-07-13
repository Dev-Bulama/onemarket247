@extends('layouts.storefront')

@section('title', 'Terms of Service')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8 prose prose-sm">
        <h1 class="text-2xl font-bold text-gray-900">Terms of Service</h1>
        <p class="text-sm text-gray-500">Last updated: {{ now()->format('F Y') }}</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">1. About {{ config('app.name') }}</h2>
        <p class="mt-2 text-sm text-gray-700">{{ config('app.name') }} is a marketplace that connects independent vendors with customers. Vendors are independently owned and operated businesses; {{ config('app.name') }} is not the seller of record for vendor-listed products unless stated otherwise.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">2. Accounts</h2>
        <p class="mt-2 text-sm text-gray-700">You are responsible for maintaining the confidentiality of your account credentials and for all activity under your account. Notify us immediately of any unauthorized use.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">3. Orders and Payment</h2>
        <p class="mt-2 text-sm text-gray-700">Placing an order is an offer to purchase, subject to acceptance and stock availability. Prices and availability are set by the selling vendor and may change without notice until an order is confirmed.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">4. Vendor Conduct</h2>
        <p class="mt-2 text-sm text-gray-700">Vendors must list products accurately, fulfil orders promptly, and comply with applicable law. {{ config('app.name') }} may suspend or terminate a vendor's store for violations of these terms.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">5. Returns and Refunds</h2>
        <p class="mt-2 text-sm text-gray-700">Return and refund eligibility is shown at checkout and in your order details, and may vary by vendor and product category.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">6. Limitation of Liability</h2>
        <p class="mt-2 text-sm text-gray-700">{{ config('app.name') }} facilitates transactions between customers and vendors and, to the fullest extent permitted by law, is not liable for indirect or consequential damages arising from a vendor's products or conduct.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">7. Changes to These Terms</h2>
        <p class="mt-2 text-sm text-gray-700">We may update these terms from time to time. Continued use of the platform after a change constitutes acceptance of the updated terms.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">8. Contact</h2>
        <p class="mt-2 text-sm text-gray-700">Questions about these terms can be sent through our <a href="{{ route('pages.contact') }}" class="text-indigo-600 hover:underline">contact page</a>.</p>
    </div>
@endsection
