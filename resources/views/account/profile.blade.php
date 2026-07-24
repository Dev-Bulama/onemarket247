@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Profile</h1>

    <form method="POST" action="{{ route('account.profile.update') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-lg">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
        </div>

        <div>
            <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Date of birth</label>
            <input id="date_of_birth" type="date" name="date_of_birth"
                   value="{{ old('date_of_birth', $customerProfile?->date_of_birth?->format('Y-m-d')) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange">
        </div>

        <div>
            <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
            <select id="gender" name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">—</option>
                @foreach ($genders as $gender)
                    <option value="{{ $gender->value }}" @selected(old('gender', $customerProfile?->gender?->value) === $gender->value)>
                        {{ $gender->getLabel() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="preferred_language_id" class="block text-sm font-medium text-gray-700">Preferred language</label>
            <select id="preferred_language_id" name="preferred_language_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">—</option>
                @foreach ($languages as $language)
                    <option value="{{ $language->id }}" @selected(old('preferred_language_id', $customerProfile?->preferred_language_id) == $language->id)>
                        {{ $language->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="preferred_currency_id" class="block text-sm font-medium text-gray-700">Preferred currency</label>
            <select id="preferred_currency_id" name="preferred_currency_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">—</option>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}" @selected(old('preferred_currency_id', $customerProfile?->preferred_currency_id) == $currency->id)>
                        {{ $currency->name }} ({{ $currency->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="marketing_opt_in" value="1"
                   @checked(old('marketing_opt_in', $customerProfile?->marketing_opt_in)) class="rounded border-gray-300">
            Send me marketing emails and offers
        </label>

        <button type="submit" class="rounded-md bg-brand-orange px-4 py-2 text-white font-medium hover:bg-brand-orange2">
            Save changes
        </button>
    </form>
@endsection
