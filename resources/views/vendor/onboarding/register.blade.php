<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($currentLanguage ?? null)?->isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Become a vendor — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.tailwind-brand-config')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="font-sans min-h-screen bg-warm px-4 py-12">
<div class="mx-auto max-w-3xl">
    <div class="text-center mb-6 flex flex-col items-center">
        @include('storefront.partials.logo')
        <p class="text-body mt-1">Sell on {{ config('app.name') }} — apply to become a vendor</p>
    </div>

    <div class="bg-white shadow rounded-lg p-8">
        @if ($errors->any())
            <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <nav class="mb-8 flex justify-between text-sm font-medium text-gray-400" id="step-nav">
            <button type="button" data-step="1" class="step-tab text-brand-orange">1. Business</button>
            <button type="button" data-step="2" class="step-tab">2. Store</button>
            <button type="button" data-step="3" class="step-tab">3. Banking</button>
            <button type="button" data-step="4" class="step-tab">4. Documents &amp; terms</button>
        </nav>

        <form method="POST" action="{{ route('vendor.register') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <section data-step-panel="1" class="space-y-4">
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
                <div>
                    <label class="block text-sm font-medium text-gray-700">Business name</label>
                    <input type="text" name="business_name" value="{{ old('business_name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Business registration number</label>
                    <input type="text" name="registration_number" value="{{ old('registration_number') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tax identification number</label>
                    <input type="text" name="tax_identification_number" value="{{ old('tax_identification_number') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Agent info</h3>
                    <p class="text-xs text-gray-500">Only fill this in if a OneMarket247 field agent is assisting with this application.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Agent ID No.</label>
                    <input type="text" name="agent_id_number" value="{{ old('agent_id_number') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Agent full name</label>
                    <input type="text" name="agent_full_name" value="{{ old('agent_full_name') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Agent phone no.</label>
                    <input type="text" name="agent_phone" value="{{ old('agent_phone') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <button type="button" class="step-next rounded-md bg-brand-orange px-4 py-2 text-white font-medium hover:bg-brand-orange2" data-next="2">Next: Store</button>
            </section>

            <section data-step-panel="2" class="space-y-4 hidden">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Store name</label>
                    <input type="text" name="store_name" value="{{ old('store_name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Store category</label>
                    <input type="text" name="store_category" value="{{ old('store_category') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Store description</label>
                    <textarea name="store_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('store_description') }}</textarea>
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
                    <label class="block text-sm font-medium text-gray-700">Postal code</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <textarea name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('address') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Website</label>
                    <input type="url" name="website" value="{{ old('website') }}" placeholder="https://"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                @if ($plans->isNotEmpty())
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subscription plan</label>
                        <select name="vendor_subscription_plan_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('vendor_subscription_plan_id', $plans->firstWhere('is_default', true)?->id) == $plan->id)>
                                    {{ $plan->name }} ({{ $plan->isFree() ? 'Free' : number_format($plan->price / 100, 2).' / '.$plan->billing_period->getLabel() }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="flex justify-between">
                    <button type="button" class="step-prev rounded-md border border-gray-300 px-4 py-2 text-gray-700" data-prev="1">Back</button>
                    <button type="button" class="step-next rounded-md bg-brand-orange px-4 py-2 text-white font-medium hover:bg-brand-orange2" data-next="3">Next: Banking</button>
                </div>
            </section>

            <section data-step-panel="3" class="space-y-4 hidden">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bank name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bank account name</label>
                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bank account number</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="flex justify-between">
                    <button type="button" class="step-prev rounded-md border border-gray-300 px-4 py-2 text-gray-700" data-prev="2">Back</button>
                    <button type="button" class="step-next rounded-md bg-brand-orange px-4 py-2 text-white font-medium hover:bg-brand-orange2" data-next="4">Next: Documents</button>
                </div>
            </section>

            <section data-step-panel="4" class="space-y-4 hidden">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Identity document</label>
                    <input type="file" name="identity_document" required class="mt-1 block w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Business registration document</label>
                    <input type="file" name="business_registration_document" required class="mt-1 block w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tax certificate (optional)</label>
                    <input type="file" name="tax_certificate_document" class="mt-1 block w-full">
                </div>
                <label class="flex items-start gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="terms" value="1" required class="mt-1 rounded border-gray-300">
                    I agree to the OneMarket247 vendor terms and confirm the information above is accurate.
                </label>
                <div class="flex justify-between">
                    <button type="button" class="step-prev rounded-md border border-gray-300 px-4 py-2 text-gray-700" data-prev="3">Back</button>
                    <button type="submit" class="rounded-md bg-brand-orange px-6 py-2 text-white font-medium hover:bg-brand-orange2">Submit application</button>
                </div>
            </section>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-gray-600">
        Already have a store? <a href="{{ route('vendor.login') }}" class="text-brand-orange font-medium">Sign in</a>
    </p>
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

    document.querySelectorAll('.step-next, .step-prev').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.next || btn.dataset.prev;
            document.querySelectorAll('[data-step-panel]').forEach(p => p.classList.add('hidden'));
            document.querySelector(`[data-step-panel="${target}"]`).classList.remove('hidden');
            document.querySelectorAll('.step-tab').forEach(t => t.classList.remove('text-brand-orange'));
            document.querySelector(`.step-tab[data-step="${target}"]`).classList.add('text-brand-orange');
        });
    });

    document.querySelectorAll('.step-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.step;
            document.querySelectorAll('[data-step-panel]').forEach(p => p.classList.add('hidden'));
            document.querySelector(`[data-step-panel="${target}"]`).classList.remove('hidden');
            document.querySelectorAll('.step-tab').forEach(t => t.classList.remove('text-brand-orange'));
            tab.classList.add('text-brand-orange');
        });
    });
</script>
</body>
</html>
