@extends('layouts.guest')

@section('title', 'Application submitted')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-2">Application submitted</h1>
    <p class="text-sm text-gray-600 mb-6">
        Thanks for applying to become a OneMarket247 field agent. We'll review your application and
        email you at the address you provided once a decision is made.
    </p>
    <a href="{{ route('home') }}" class="text-brand-orange font-medium text-sm">Return to homepage</a>
@endsection
