@extends('layouts.guest')

@section('title', 'Verify email')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-2">Verify your email address</h1>
    <p class="text-sm text-gray-600 mb-6">
        Thanks for signing up! Before getting started, please verify your email address by
        clicking the link we just emailed to you.
    </p>

    @if (session('status') === 'verification-link-sent')
        <p class="mb-4 text-sm text-green-700">A new verification link has been sent to your email address.</p>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="w-full rounded-md bg-brand-orange px-4 py-2 text-white font-medium hover:bg-brand-orange2">
            Resend verification email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full text-sm text-gray-600 hover:underline">Log out</button>
    </form>
@endsection
