<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'integer'],
            'state_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
        ]);

        $country = Country::where('id', $data['country_id'])->where('is_active', true)->firstOrFail();

        $state = ($data['state_id'] ?? null)
            ? State::where('id', $data['state_id'])->where('country_id', $country->id)->firstOrFail()
            : null;

        $city = ($data['city_id'] ?? null)
            ? City::where('id', $data['city_id'])->where('state_id', $state?->id)->firstOrFail()
            : null;

        session(['delivery_location' => [
            'country_id' => $country->id,
            'state_id' => $state?->id,
            'city_id' => $city?->id,
        ]]);

        return back();
    }
}
