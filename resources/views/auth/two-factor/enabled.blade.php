@extends('layouts.app')

@section('title', 'Two-factor authentication')

@section('content')
    <div class="max-w-md">
        <h1 class="text-lg font-semibold text-gray-900 mb-2">Two-factor authentication is enabled</h1>
        <p class="text-sm text-gray-600 mb-6">Your account is protected with an authenticator app.</p>

        @if (session('recovery_codes'))
            <div class="mb-6 rounded-md bg-amber-50 border border-amber-200 px-4 py-3">
                <p class="text-sm font-medium text-amber-800 mb-2">
                    Save these recovery codes somewhere safe. Each can be used once if you lose
                    access to your authenticator app. They won't be shown again.
                </p>
                <ul class="grid grid-cols-2 gap-1 font-mono text-sm text-amber-900">
                    @foreach (session('recovery_codes') as $code)
                        <li>{{ $code }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('two-factor.disable') }}" class="space-y-4">
            @csrf
            @method('DELETE')

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Confirm your password to disable</label>
                <input id="password" type="password" name="password" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-700">
                Disable two-factor authentication
            </button>
        </form>
    </div>
@endsection
