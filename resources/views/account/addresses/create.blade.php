@extends('layouts.app')

@section('title', 'Add address')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Add address</h1>

    <form method="POST" action="{{ route('account.addresses.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-lg">
        @csrf

        @include('account.addresses._form')

        <button type="submit" class="rounded-md bg-brand-orange px-4 py-2 text-white font-medium hover:bg-brand-orange2">
            Save address
        </button>
    </form>
@endsection
