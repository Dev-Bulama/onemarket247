@extends('layouts.guest')

@section('title', 'Application submitted')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-2">Application submitted</h1>
    <p class="text-sm text-gray-600 mb-6">
        Thanks for applying to sell on OneMarket247. We'll review your application and email you
        at the address you provided. If approved, you'll receive a link to set your store password.
    </p>
    <a href="{{ route('home') }}" class="text-indigo-600 font-medium text-sm">Return to homepage</a>
@endsection
