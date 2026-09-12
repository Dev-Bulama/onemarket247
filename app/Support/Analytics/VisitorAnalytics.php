<?php

namespace App\Support\Analytics;

use App\Models\Country;
use App\Models\SiteVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Site-visitor and location analytics, shared by the admin (platform-wide,
 * $vendorId = null) and vendor ($vendorId scopes to that vendor's store
 * page visits) dashboards. Backed by SiteVisit rows recorded by the
 * TrackSiteVisit middleware — see its docblock for what's tracked and why
 * country_code can be null ("not yet resolved") or 'XX' ("resolution was
 * attempted and failed/unknown").
 */
class VisitorAnalytics
{
    /**
     * @return array{visits: int, unique_visitors: int}
     */
    public function summary(?int $vendorId, Carbon $from, Carbon $to): array
    {
        $query = $this->baseQuery($vendorId, $from, $to);

        return [
            'visits' => (clone $query)->count(),
            'unique_visitors' => (clone $query)->distinct()->count('visitor_id'),
        ];
    }

    /**
     * Day => visit count, every day in range present even if 0.
     *
     * @return Collection<string, int>
     */
    public function dailyVisits(?int $vendorId, Carbon $from, Carbon $to): Collection
    {
        $rows = $this->baseQuery($vendorId, $from, $to)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as visits')
            ->groupBy('date')
            ->pluck('visits', 'date');

        $filled = new Collection;

        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $filled->put($key, (int) ($rows[$key] ?? 0));
        }

        return $filled;
    }

    /**
     * Top countries by visit count, excluding not-yet-resolved (null) and
     * unresolvable ('XX') visits.
     *
     * @return Collection<int, array{country_code: string, country_name: string, visits: int}>
     */
    public function topCountries(?int $vendorId, Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        $rows = $this->baseQuery($vendorId, $from, $to)
            ->whereNotNull('country_code')
            ->where('country_code', '!=', 'XX')
            ->selectRaw('country_code, COUNT(*) as visits')
            ->groupBy('country_code')
            ->orderByDesc('visits')
            ->limit($limit)
            ->get();

        $names = Country::query()
            ->whereIn('iso2', $rows->pluck('country_code'))
            ->pluck('name', 'iso2');

        return $rows->map(fn ($row) => [
            'country_code' => $row->country_code,
            'country_name' => $names[$row->country_code] ?? $row->country_code,
            'visits' => (int) $row->visits,
        ]);
    }

    private function baseQuery(?int $vendorId, Carbon $from, Carbon $to): Builder
    {
        return SiteVisit::query()
            ->when($vendorId !== null, fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->whereBetween('created_at', [$from, $to]);
    }
}
