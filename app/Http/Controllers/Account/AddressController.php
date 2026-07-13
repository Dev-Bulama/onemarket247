<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddressRequest;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.addresses.index', [
            'addresses' => $request->user()->addresses()->orderByDesc('is_default_shipping')->get(),
        ]);
    }

    public function create(): View
    {
        return view('account.addresses.create', $this->geographyData());
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $data = $this->withDefaultFlags($request);

        $address = $request->user()->addresses()->create($data);

        $this->applyDefaults($request->user(), $address, $data);

        return redirect()->route('account.addresses.index')->with('status', 'address-created');
    }

    public function edit(Request $request, Address $address): View
    {
        Gate::authorize('view', $address);

        return view('account.addresses.edit', [
            'address' => $address,
            ...$this->geographyData(),
        ]);
    }

    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        $data = $this->withDefaultFlags($request);

        $address->update($data);

        $this->applyDefaults($request->user(), $address, $data);

        return redirect()->route('account.addresses.index')->with('status', 'address-updated');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        Gate::authorize('delete', $address);

        $address->delete();

        return redirect()->route('account.addresses.index')->with('status', 'address-deleted');
    }

    private function withDefaultFlags(AddressRequest $request): array
    {
        return [
            ...$request->validated(),
            'is_default_shipping' => $request->boolean('is_default_shipping'),
            'is_default_billing' => $request->boolean('is_default_billing'),
        ];
    }

    private function applyDefaults(mixed $user, Address $address, array $data): void
    {
        if (! empty($data['is_default_shipping'])) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_shipping' => false]);
        }

        if (! empty($data['is_default_billing'])) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_billing' => false]);
        }
    }

    private function geographyData(): array
    {
        return [
            'countries' => Country::where('is_active', true)->orderBy('name')->get(),
            'states' => State::where('is_active', true)->orderBy('name')->get(['id', 'country_id', 'name']),
            'cities' => City::where('is_active', true)->orderBy('name')->get(['id', 'state_id', 'name']),
        ];
    }
}
