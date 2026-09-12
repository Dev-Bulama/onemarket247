<?php

namespace App\Support\Analytics;

use App\Models\IpGeolocation;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Resolves an IP address to a 2-letter country code via ip-api.com's free,
 * keyless endpoint, caching every lookup in ip_geolocations so the same IP
 * is never looked up twice. Used out of band by ResolveSiteVisitCountries
 * — never called from a storefront request, since a network call per
 * pageview would add real latency (see TrackSiteVisit's docblock).
 *
 * ip-api.com's free tier is rate-limited (45 requests/minute) and has no
 * uptime guarantee; a failed or unrecognised lookup is cached with a null
 * country_code so it's not retried every run, and callers simply see no
 * country for that visit rather than an error.
 */
class IpGeolocationResolver
{
    public function resolve(string $ipAddress): ?string
    {
        $cached = IpGeolocation::query()->find($ipAddress);

        if ($cached) {
            return $cached->country_code;
        }

        $countryCode = $this->lookup($ipAddress);

        IpGeolocation::query()->create([
            'ip_address' => $ipAddress,
            'country_code' => $countryCode,
            'resolved_at' => now(),
        ]);

        return $countryCode;
    }

    private function lookup(string $ipAddress): ?string
    {
        if (in_array($ipAddress, ['127.0.0.1', '::1'], true)) {
            return null;
        }

        try {
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ipAddress}", [
                'fields' => 'status,countryCode',
            ]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful() || $response->json('status') !== 'success') {
            return null;
        }

        return $response->json('countryCode');
    }
}
