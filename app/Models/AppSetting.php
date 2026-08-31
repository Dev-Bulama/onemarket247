<?php

namespace App\Models;

use App\Enums\AppEnvironment;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'active_environment', 'local_api_url', 'production_api_url', 'force_production',
        'app_name', 'logo_url', 'splash_logo_url', 'min_app_version', 'product_grid_columns',
    ];

    protected function casts(): array
    {
        return [
            'active_environment' => AppEnvironment::class,
            'force_production' => 'boolean',
            'product_grid_columns' => 'integer',
        ];
    }

    /**
     * The single row every part of the app reads/writes — same singleton
     * shape as App\Models\MailSetting / App\Models\PushSetting.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    /**
     * What GET /api/v1/bootstrap tells the mobile app to actually point
     * its API calls at. force_production is a deliberate safety net: an
     * admin experimenting with "local" for their own testing can't
     * accidentally strand every installed app on an unreachable local
     * server just by forgetting to flip active_environment back.
     */
    public function resolveApiBaseUrl(): ?string
    {
        if ($this->force_production || $this->active_environment === AppEnvironment::Production) {
            return $this->production_api_url;
        }

        return $this->local_api_url ?: $this->production_api_url;
    }
}
