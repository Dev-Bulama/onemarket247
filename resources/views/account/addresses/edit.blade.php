@extends('layouts.app')

@section('title', 'Edit address')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Edit address</h1>

    <form method="POST" action="{{ route('account.addresses.update', $address) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-lg">
        @csrf
        @method('PUT')

        @include('account.addresses._form')

        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
            Save address
        </button>
    </form>
@endsection
