<?php

namespace App\Console\Commands;

use App\Actions\Location\PruneLocationHistoryAction;
use Illuminate\Console\Command;

/**
 * Enforces the location-history retention window daily — see
 * App\Actions\Location\PruneLocationHistoryAction. Scheduled in
 * routes/console.php; requires the server's crontab to run
 * `php artisan schedule:run` every minute, same as any other scheduled
 * command in this app.
 */
class PruneLocationHistory extends Command
{
    protected $signature = 'location:prune-history';

    protected $description = 'Delete location pings older than the configured retention window';

    public function handle(PruneLocationHistoryAction $action): int
    {
        $deleted = $action->handle();

        $this->info("Deleted {$deleted} location ping(s) past the retention window.");

        return self::SUCCESS;
    }
}
