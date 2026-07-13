@php
    $address = $address ?? null;
@endphp

<div>
    <label for="label" class="block text-sm font-medium text-gray-700">Label</label>
    <input id="label" type="text" name="label" value="{{ old('label', $address?->label) }}" placeholder="Home, Office, ..." required
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
</div>

<div>
    <label for="full_name" class="block text-sm font-medium text-gray-700">Full name</label>
    <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $address?->full_name) }}" required
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
</div>

<div>
    <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
    <input id="phone" type="text" name="phone" value="{{ old('phone', $address?->phone) }}"
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
</div>

<div class="grid grid-cols-3 gap-4">
    <div>
        <label for="country_id" class="block text-sm font-medium text-gray-700">Country</label>
        <select name="country_id" id="country_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">—</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected(old('country_id', $address?->country_id) == $country->id)>{{ $country->name }}</option>
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
    <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code', $address?->postal_code) }}"
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
</div>

<div>
    <label for="address_line_1" class="block text-sm font-medium text-gray-700">Address line 1</label>
    <input id="address_line_1" type="text" name="address_line_1" value="{{ old('address_line_1', $address?->address_line_1) }}" required
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
</div>

<div>
    <label for="address_line_2" class="block text-sm font-medium text-gray-700">Address line 2</label>
    <input id="address_line_2" type="text" name="address_line_2" value="{{ old('address_line_2', $address?->address_line_2) }}"
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
</div>

<label class="flex items-center gap-2 text-sm text-gray-600">
    <input type="checkbox" name="is_default_shipping" value="1" @checked(old('is_default_shipping', $address?->is_default_shipping)) class="rounded border-gray-300">
    Use as default shipping address
</label>

<label class="flex items-center gap-2 text-sm text-gray-600">
    <input type="checkbox" name="is_default_billing" value="1" @checked(old('is_default_billing', $address?->is_default_billing)) class="rounded border-gray-300">
    Use as default billing address
</label>

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
        populateStates(initialCountry, '{{ old('state_id', $address?->state_id) }}');
        populateCities('{{ old('state_id', $address?->state_id) }}', '{{ old('city_id', $address?->city_id) }}');
    }
</script>
