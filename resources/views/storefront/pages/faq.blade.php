@extends('layouts.storefront')

@section('title', 'Frequently Asked Questions')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8">
        <h1 class="text-2xl font-bold text-gray-900">Frequently Asked Questions</h1>

        <dl class="mt-6 space-y-6">
            <div>
                <dt class="font-semibold text-gray-900">How do I place an order?</dt>
                <dd class="mt-1 text-sm text-gray-700">Browse the shop or a store page, choose a product, and follow the checkout steps. You'll receive an order confirmation by email once your payment is confirmed.</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900">Can I buy from more than one vendor in a single order?</dt>
                <dd class="mt-1 text-sm text-gray-700">Yes. Your cart can hold items from multiple stores; at checkout we split them into separate vendor shipments while still giving you one order to track.</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900">How do I become a vendor?</dt>
                <dd class="mt-1 text-sm text-gray-700">Apply from the <a href="{{ route('vendor.register') }}" class="text-brand-orange hover:underline">vendor registration page</a>. Most applications are reviewed within a few business days.</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900">What payment methods are accepted?</dt>
                <dd class="mt-1 text-sm text-gray-700">Supported payment methods are shown at checkout and vary by region.</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900">How do returns work?</dt>
                <dd class="mt-1 text-sm text-gray-700">Each order includes the applicable return window and process for its vendor. You can start a return from your order history once orders are available in your account.</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900">I still need help — who do I contact?</dt>
                <dd class="mt-1 text-sm text-gray-700">Reach out through our <a href="{{ route('pages.contact') }}" class="text-brand-orange hover:underline">contact page</a> and we'll get back to you.</dd>
            </div>
        </dl>
    </div>
@endsection
