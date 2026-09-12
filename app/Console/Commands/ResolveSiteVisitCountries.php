<?php

namespace App\Console\Commands;

use App\Models\SiteVisit;
use App\Support\Analytics\IpGeolocationResolver;
use Illuminate\Console\Command;

/**
 * Batch-resolves the country_code of any SiteVisit recorded with no IP
 * lookup yet (see TrackSiteVisit's docblock for why that's done lazily
 * here rather than inline on every pageview). Scheduled hourly in
 * routes/console.php — requires the server's crontab to run
 * `php artisan schedule:run` every minute, same as any other scheduled
 * command in this app.
 *
 * A lookup that genuinely can't be resolved (private/local IP, API
 * unavailable, rate-limited) is stamped with the 'XX' sentinel rather than
 * left null, so it counts as "attempted" and isn't re-selected by this
 * command forever — SalesAnalytics/VisitorAnalytics treat 'XX' as unknown
 * and exclude it from country breakdowns.
 */
class ResolveSiteVisitCountries extends Command
{
    protected $signature = 'analytics:resolve-site-visit-countries {--limit=500}';

    protected $description = 'Resolve the country of any recent site visit that has no country_code yet';

    public function handle(IpGeolocationResolver $resolver): int
    {
        $visits = SiteVisit::query()
            ->whereNull('country_code')
            ->whereNotNull('ip_address')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($visits->isEmpty()) {
            $this->info('No unresolved site visits.');

            return self::SUCCESS;
        }

        $resolved = 0;

        foreach ($visits->groupBy('ip_address') as $ipAddress => $visitsForIp) {
            $countryCode = $resolver->resolve($ipAddress);

            SiteVisit::query()
                ->whereIn('id', $visitsForIp->pluck('id'))
                ->update(['country_code' => $countryCode ?? 'XX']);

            $resolved += $visitsForIp->count();
        }

        $this->info("Resolved {$resolved} site visit(s) across {$visits->pluck('ip_address')->unique()->count()} IP address(es).");

        return self::SUCCESS;
    }
}
