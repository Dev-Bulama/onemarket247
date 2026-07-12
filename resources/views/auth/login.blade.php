@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Log in to your account</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" type="password" name="password" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">Forgot password?</a>
        </div>

        <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
            Log in
        </button>
    </form>

    @if (config('services.google.client_id') || config('services.facebook.client_id'))
        <div class="mt-6 space-y-2">
            @if (config('services.google.client_id'))
                <a href="{{ route('social.redirect', 'google') }}"
                   class="block w-full text-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Continue with Google
                </a>
            @endif
            @if (config('services.facebook.client_id'))
                <a href="{{ route('social.redirect', 'facebook') }}"
                   class="block w-full text-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Continue with Facebook
                </a>
            @endif
        </div>
    @endif

    <p class="mt-6 text-center text-sm text-gray-600">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Register</a>
    </p>
@endsection
