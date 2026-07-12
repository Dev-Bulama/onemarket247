@extends('layouts.guest')

@section('title', 'Two-factor challenge')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-2">Two-factor verification</h1>
    <p class="text-sm text-gray-600 mb-6">Enter the code from your authenticator app, or one of your recovery codes.</p>

    <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-gray-700">Code</label>
            <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
            Verify
        </button>
    </form>
@endsection
