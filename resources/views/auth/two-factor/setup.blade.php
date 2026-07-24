@extends('layouts.app')

@section('title', 'Set up two-factor authentication')

@section('content')
    <div class="max-w-md">
        <h1 class="text-lg font-semibold text-gray-900 mb-2">Set up two-factor authentication</h1>
        <p class="text-sm text-gray-600 mb-6">
            Scan this QR code with an authenticator app (Google Authenticator, Authy, 1Password, etc.),
            then enter the 6-digit code it generates to confirm setup.
        </p>

        <div class="bg-white border border-gray-200 rounded-md p-4 mb-6 flex justify-center">
            {!! $qrCodeSvg !!}
        </div>

        <p class="text-xs text-gray-500 mb-6">Can't scan? Enter this code manually: <span class="font-mono">{{ $secret }}</span></p>

        <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-4">
            @csrf

            <div>
                <label for="code" class="block text-sm font-medium text-gray-700">Verification code</label>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
            </div>

            <button type="submit" class="w-full rounded-md bg-brand-orange px-4 py-2 text-white font-medium hover:bg-brand-orange2">
                Confirm and enable
            </button>
        </form>
    </div>
@endsection
