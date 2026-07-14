<?php

namespace App\Http\Middleware;

use App\Actions\Shipping\ResolveShippingZoneAction;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the customer's session-persisted delivery location (set via
 * LocationController::switch()) into real Country/State/City models and,
 * using the same specificity-ordered lookup checkout itself relies on,
 * whether that location actually has a matching shipping zone — so the
 * header can show "Delivering to: X" (or "not deliverable here") without
 * a customer having to reach checkout first.
 */
class SetDeliveryLocation
{
    public function handle(Request $request, Closure $next): Response
    {
        $location = session('delivery_location');

        $country = $location['country_id'] ?? null
            ? Country::where('id', $location['country_id'])->where('is_active', true)->first()
            : null;

        $state = $country && ($location['state_id'] ?? null)
            ? State::where('id', $location['state_id'])->where('country_id', $country->id)->first()
            : null;

        $city = $state && ($location['city_id'] ?? null)
            ? City::where('id', $location['city_id'])->where('state_id', $state->id)->first()
            : null;

        $deliverable = $country
            ? app(ResolveShippingZoneAction::class)->handle($country->id, $state?->id, $city?->id) !== null
            : null;

        View::share('deliveryLocation', $country ? ['country' => $country, 'state' => $state, 'city' => $city, 'deliverable' => $deliverable] : null);

        return $next($request);
    }
}
