@extends('layouts.app')

@section('title', 'Addresses')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-lg font-semibold text-gray-900">Addresses</h1>
        <a href="{{ route('account.addresses.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
            Add address
        </a>
    </div>

    @if ($addresses->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-sm text-gray-500">
            You haven't added any addresses yet.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($addresses as $address)
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="font-medium text-gray-900">{{ $address->label }}</div>
                        <div class="flex gap-1">
                            @if ($address->is_default_shipping)
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs text-green-700">Default shipping</span>
                            @endif
                            @if ($address->is_default_billing)
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700">Default billing</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ $address->full_name }}</p>
                    <p class="text-sm text-gray-600">{{ $address->address_line_1 }}</p>
                    @if ($address->address_line_2)
                        <p class="text-sm text-gray-600">{{ $address->address_line_2 }}</p>
                    @endif
                    <p class="text-sm text-gray-600">
                        {{ collect([$address->city?->name, $address->state?->name, $address->country?->name, $address->postal_code])->filter()->implode(', ') }}
                    </p>
                    @if ($address->phone)
                        <p class="text-sm text-gray-600">{{ $address->phone }}</p>
                    @endif

                    <div class="mt-4 flex items-center gap-4 text-sm">
                        <a href="{{ route('account.addresses.edit', $address) }}" class="text-indigo-600 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm('Delete this address?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
