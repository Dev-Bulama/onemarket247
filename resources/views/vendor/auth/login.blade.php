@extends('layouts.guest')

@section('title', 'Vendor log in')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Vendor sign in</h1>

    <form method="POST" action="{{ route('vendor.login') }}" class="space-y-4">
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

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" class="rounded border-gray-300">
            Remember me
        </label>

        <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
            Sign in to your store
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Forgot your password? <a href="{{ route('vendor.password.request') }}" class="text-indigo-600 font-medium">Reset it</a>
    </p>
    <p class="mt-2 text-center text-sm text-gray-600">
        Want to sell on OneMarket247? <a href="{{ route('vendor.register') }}" class="text-indigo-600 font-medium">Apply now</a>
    </p>
@endsection
