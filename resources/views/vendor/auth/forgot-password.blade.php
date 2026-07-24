@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-2">Forgot your store password?</h1>
    <p class="text-sm text-gray-600 mb-6">Enter your email and we'll send you a reset link.</p>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('vendor.password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full rounded-md bg-brand-orange px-4 py-2 text-white font-medium hover:bg-brand-orange2">
            Email password reset link
        </button>
    </form>
@endsection
