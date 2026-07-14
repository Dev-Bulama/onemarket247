@extends('layouts.storefront')

@section('title', 'Checkout')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Checkout</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('checkout.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4">
                @csrf
                <input type="hidden" name="checkout_session_key" value="{{ $session->idempotency_key }}">

                <h2 class="font-medium text-gray-900">Shipping details</h2>

                @guest
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                @endguest

                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full name</label>
                    <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $defaultAddress?->full_name) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone', $defaultAddress?->phone) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="country_id" class="block text-sm font-medium text-gray-700">Country</label>
                        <select name="country_id" id="country_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">—</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" @selected(old('country_id', $defaultAddress?->country_id ?? $deliveryLocation['country']->id ?? null) == $country->id)>{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="state_id" class="block text-sm font-medium text-gray-700">State</label>
                        <select name="state_id" id="state_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></select>
                    </div>
                    <div>
                        <label for="city_id" class="block text-sm font-medium text-gray-700">City</label>
                        <select name="city_id" id="city_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></select>
                    </div>
                </div>

                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700">Postal code</label>
                    <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code', $defaultAddress?->postal_code) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <label for="address_line_1" class="block text-sm font-medium text-gray-700">Address line 1</label>
                    <input id="address_line_1" type="text" name="address_line_1" value="{{ old('address_line_1', $defaultAddress?->address_line_1) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <label for="address_line_2" class="block text-sm font-medium text-gray-700">Address line 2</label>
                    <input id="address_line_2" type="text" name="address_line_2" value="{{ old('address_line_2', $defaultAddress?->address_line_2) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div class="rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    Payment collection isn't live yet — placing your order reserves your items and we'll follow up with
                    payment instructions by email.
                </div>

                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                    Place order
                </button>
            </form>
        </div>

        <div class="bg-white shadow rounded-lg p-6 h-fit space-y-4">
            <h2 class="font-medium text-gray-900">Order summary</h2>

            <div class="divide-y divide-gray-100">
                @foreach ($cart->activeItems as $item)
                    <div class="py-3 flex items-center justify-between text-sm">
                        <div>
                            <p class="text-gray-900">{{ $item->product->name }}</p>
                            <p class="text-xs text-gray-500">Qty {{ $item->quantity }}</p>
                        </div>
                        <p class="text-gray-900">${{ number_format($item->lineTotal() / 100, 2) }}</p>
                    </div>
                @endforeach
            </div>

            <dl class="space-y-2 text-sm border-t border-gray-100 pt-4">
                <div class="flex justify-between">
                    <dt class="text-gray-600">Subtotal</dt>
                    <dd class="text-gray-900">${{ number_format($cart->subtotal() / 100, 2) }}</dd>
                </div>
                @if ($cart->discount() > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Discount</dt>
                        <dd class="text-green-700">-${{ number_format($cart->discount() / 100, 2) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-100 pt-2">
                    <dt>Estimated total</dt>
                    <dd>${{ number_format($cart->total() / 100, 2) }}</dd>
                </div>
            </dl>

            <p class="text-xs text-gray-500">Tax and shipping are not yet calculated and will show as $0 on your order.</p>
        </div>
    </div>

    <script>
        const states = @json($states);
        const cities = @json($cities);

        function populateStates(countryId, selected) {
            const stateSelect = document.getElementById('state_id');
            stateSelect.innerHTML = '<option value="">—</option>';
            states.filter(s => s.country_id == countryId).forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                if (selected && selected == s.id) opt.selected = true;
                stateSelect.appendChild(opt);
            });
            populateCities(stateSelect.value);
        }

        function populateCities(stateId, selected) {
            const citySelect = document.getElementById('city_id');
            citySelect.innerHTML = '<option value="">—</option>';
            cities.filter(c => c.state_id == stateId).forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                if (selected && selected == c.id) opt.selected = true;
                citySelect.appendChild(opt);
            });
        }

        document.getElementById('country_id').addEventListener('change', (e) => populateStates(e.target.value));
        document.getElementById('state_id').addEventListener('change', (e) => populateCities(e.target.value));

        const initialCountry = document.getElementById('country_id').value;
        if (initialCountry) {
            populateStates(initialCountry, '{{ old('state_id', $defaultAddress?->state_id ?? $deliveryLocation['state']->id ?? null) }}');
            populateCities('{{ old('state_id', $defaultAddress?->state_id ?? $deliveryLocation['state']->id ?? null) }}', '{{ old('city_id', $defaultAddress?->city_id ?? $deliveryLocation['city']->id ?? null) }}');
        }
    </script>
@endsection
