<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($currentLanguage ?? null)?->isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Become an agent — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.tailwind-brand-config')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="font-sans min-h-screen bg-warm px-4 py-12">
<div class="mx-auto max-w-2xl">
    <div class="text-center mb-6 flex flex-col items-center">
        @include('storefront.partials.logo')
        <p class="text-body mt-1">Become a {{ config('app.name') }} field agent</p>
    </div>

    <div class="bg-white shadow rounded-lg p-8">
        <p class="text-sm text-gray-600 mb-6">
            OneMarket247 field agents help customers apply to become vendors. Once approved, you'll
            appear in the "Registered Agent" list vendors can pick from when they apply.
        </p>

        @if ($errors->any())
            <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('agent.apply') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">Full name</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Country</label>
                    <select name="country_id" id="country_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">—</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected(old('country_id') == $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">State</label>
                    <select name="state_id" id="state_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">City</label>
                    <select name="city_id" id="city_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <textarea name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('address') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Postal code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">ID type</label>
                    <input type="text" name="identity_type" value="{{ old('identity_type') }}" placeholder="e.g. National ID, Passport"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">ID number</label>
                    <input type="text" name="identity_number" value="{{ old('identity_number') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Notes (areas you cover, experience, etc.)</label>
                <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-gray-200 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">Documents</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Identity document</label>
                    <input type="file" name="identity_document" required class="mt-1 block w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Proof of address (optional)</label>
                    <input type="file" name="proof_of_address_document" class="mt-1 block w-full">
                </div>
            </div>

            <label class="flex items-start gap-2 text-sm text-gray-600">
                <input type="checkbox" name="terms" value="1" required class="mt-1 rounded border-gray-300">
                I agree to the OneMarket247 agent terms and confirm the information above is accurate.
            </label>

            <button type="submit" class="w-full rounded-md bg-brand-orange px-6 py-2 text-white font-medium hover:bg-brand-orange2">Submit application</button>
        </form>
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
        populateStates(initialCountry, '{{ old('state_id') }}');
    }
</script>
</body>
</html>
