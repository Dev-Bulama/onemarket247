@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Create your account</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone (optional)</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" type="password" name="password" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
        </div>

        <button type="submit" class="w-full rounded-md bg-brand-orange px-4 py-2 text-white font-medium hover:bg-brand-orange2">
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Already have an account?
        <a href="{{ route('login') }}" class="text-brand-orange hover:underline">Log in</a>
    </p>
@endsection
