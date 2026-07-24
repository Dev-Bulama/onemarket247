@extends('layouts.storefront')

@section('title', 'Privacy Policy')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8 prose prose-sm">
        <h1 class="text-2xl font-bold text-gray-900">Privacy Policy</h1>
        <p class="text-sm text-gray-500">Last updated: {{ now()->format('F Y') }}</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">1. Information We Collect</h2>
        <p class="mt-2 text-sm text-gray-700">We collect information you provide directly (account details, shipping addresses, order and payment information) and information collected automatically (device, browser, and usage data) when you use {{ config('app.name') }}.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">2. How We Use Information</h2>
        <p class="mt-2 text-sm text-gray-700">We use your information to process orders, communicate with you, operate and improve the platform, prevent fraud, and comply with legal obligations.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">3. Sharing with Vendors</h2>
        <p class="mt-2 text-sm text-gray-700">When you place an order, we share the information necessary to fulfil it (such as your name, shipping address, and order contents) with the relevant vendor.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">4. Data Security</h2>
        <p class="mt-2 text-sm text-gray-700">We use industry-standard safeguards to protect your information, including encrypted storage of sensitive fields and secure transmission of data.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">5. Your Rights</h2>
        <p class="mt-2 text-sm text-gray-700">You may access, correct, or request deletion of your personal information from your account settings, or by contacting us directly.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">6. Cookies</h2>
        <p class="mt-2 text-sm text-gray-700">We use cookies and similar technologies to keep you signed in, remember your preferences, and understand how the platform is used.</p>

        <h2 class="mt-6 text-lg font-semibold text-gray-900">7. Contact</h2>
        <p class="mt-2 text-sm text-gray-700">Questions about this policy can be sent through our <a href="{{ route('pages.contact') }}" class="text-brand-orange hover:underline">contact page</a>.</p>
    </div>
@endsection
