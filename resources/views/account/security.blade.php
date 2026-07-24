@extends('layouts.app')

@section('title', 'Security')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Security</h1>

    <div class="space-y-8">
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="font-medium text-gray-900 mb-4">Change password</h2>
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4 max-w-sm">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current password</label>
                    <input id="current_password" type="password" name="current_password" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">New password</label>
                    <input id="password" type="password" name="password" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
                </div>
                <button type="submit" class="rounded-md bg-brand-orange px-4 py-2 text-white font-medium hover:bg-brand-orange2">
                    Update password
                </button>
            </form>
        </section>

        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="font-medium text-gray-900 mb-2">Two-factor authentication</h2>
            <p class="text-sm text-gray-600 mb-4">
                Status:
                @if (auth()->user()->hasTwoFactorEnabled())
                    <span class="text-green-700 font-medium">Enabled</span>
                @else
                    <span class="text-gray-500">Not enabled</span>
                @endif
            </p>
            <a href="{{ route('two-factor.show') }}" class="text-brand-orange hover:underline text-sm">Manage two-factor authentication &rarr;</a>
        </section>

        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="font-medium text-gray-900 mb-4">Active sessions</h2>
            <div class="divide-y divide-gray-100">
                @forelse ($sessions as $deviceSession)
                    <div class="py-3 flex items-center justify-between text-sm">
                        <div>
                            <div class="text-gray-900">
                                {{ $deviceSession->ip_address }}
                                @if ($deviceSession->session_id === $currentSessionId)
                                    <span class="ml-2 text-xs text-green-700">This device</span>
                                @endif
                            </div>
                            <div class="text-gray-500">{{ $deviceSession->user_agent }} &middot; last active {{ $deviceSession->last_used_at?->diffForHumans() }}</div>
                        </div>
                        @if ($deviceSession->session_id !== $currentSessionId)
                            <form method="POST" action="{{ route('account.sessions.destroy', $deviceSession) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Revoke</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 py-3">No recorded sessions yet.</p>
                @endforelse
            </div>

            @if ($sessions->count() > 1)
                <form method="POST" action="{{ route('account.sessions.destroy-others') }}" class="mt-4 space-y-2">
                    @csrf
                    @method('DELETE')
                    <input type="password" name="password" placeholder="Confirm password" required
                           class="block w-full max-w-xs rounded-md border-gray-300 shadow-sm text-sm">
                    <button type="submit" class="text-sm text-red-600 hover:underline">Log out of all other devices</button>
                </form>
            @endif
        </section>
    </div>
@endsection
