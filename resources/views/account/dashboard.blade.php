@extends('layouts.app')

@section('title', 'My account')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">My account</h1>

    <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <div>
            <div class="text-sm text-gray-500">Name</div>
            <div class="text-gray-900">{{ auth()->user()->name }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Email</div>
            <div class="text-gray-900">
                {{ auth()->user()->email }}
                @if (auth()->user()->hasVerifiedEmail())
                    <span class="ml-2 inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs text-green-700">Verified</span>
                @else
                    <span class="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700">Unverified</span>
                @endif
            </div>
        </div>
        @if (auth()->user()->phone)
            <div>
                <div class="text-sm text-gray-500">Phone</div>
                <div class="text-gray-900">{{ auth()->user()->phone }}</div>
            </div>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('account.profile.edit') }}" class="bg-white shadow rounded-lg p-4 hover:shadow-md">
            <div class="font-medium text-gray-900">Profile</div>
            <p class="text-sm text-gray-500">Update your personal details and preferences.</p>
        </a>
        <a href="{{ route('account.addresses.index') }}" class="bg-white shadow rounded-lg p-4 hover:shadow-md">
            <div class="font-medium text-gray-900">Addresses</div>
            <p class="text-sm text-gray-500">Manage your shipping and billing addresses.</p>
        </a>
        <a href="{{ route('account.wishlist.index') }}" class="bg-white shadow rounded-lg p-4 hover:shadow-md">
            <div class="font-medium text-gray-900">Wishlist</div>
            <p class="text-sm text-gray-500">Products you've saved for later.</p>
        </a>
        <a href="{{ route('account.compare.index') }}" class="bg-white shadow rounded-lg p-4 hover:shadow-md">
            <div class="font-medium text-gray-900">Compare</div>
            <p class="text-sm text-gray-500">Products you're comparing side by side.</p>
        </a>
    </div>

    <div class="mt-6">
        <a href="{{ route('account.security') }}" class="text-indigo-600 hover:underline text-sm">Manage password, two-factor authentication, and active sessions &rarr;</a>
    </div>
@endsection
