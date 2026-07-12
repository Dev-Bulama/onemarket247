@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-2">Forgot your password?</h1>
    <p class="text-sm text-gray-600 mb-6">Enter your email and we'll send you a reset link.</p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
            Email password reset link
        </button>
    </form>
@endsection
