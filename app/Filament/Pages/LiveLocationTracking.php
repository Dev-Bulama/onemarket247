<?php

namespace App\Filament\Pages;

use App\Support\AuditLogger;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Priority 8 item 21 — a live map of consenting customers and vendor
 * owners/staff (see App\Http\Controllers\Admin\LiveLocationDataController
 * for why agents and delivery partners can never appear here). The map
 * itself lives entirely in the Blade view, polling the JSON endpoints via
 * plain fetch() rather than Livewire, since a Leaflet map's own DOM/JS
 * lifecycle doesn't need to round-trip through the server on every
 * refresh.
 */
class LiveLocationTracking extends Page
{
    protected string $view = 'filament.pages.live-location-tracking';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?string $title = 'Live Location Tracking';

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->can('location_tracking.view') ?? false;
    }

    public function mount(): void
    {
        AuditLogger::record('location.live_map_viewed', actor: Auth::guard('admin')->user());
    }
}
