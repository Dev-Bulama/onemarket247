<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Baseline housekeeping. Domain-specific scheduled jobs (exchange-rate
// refresh, abandoned-cart reminders, subscription expiry checks, backups,
// etc. — see docs/architecture/12-deployment-roadmap.md §6) are added as
// their owning phases land.
Schedule::command('queue:prune-failed --hours=48')->daily();

// Requires the server's crontab to run `php artisan schedule:run` every
// minute — see ResolveSiteVisitCountries's docblock.
Schedule::command('analytics:resolve-site-visit-countries')->hourly();
