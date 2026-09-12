<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics\AnalyticsStatsWidget;
use App\Filament\Widgets\Analytics\SalesChartWidget;
use App\Filament\Widgets\Analytics\TopCountriesWidget;
use App\Filament\Widgets\Analytics\VisitorsChartWidget;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Platform-wide sales, customer, and site-visitor analytics — the same
 * widgets (parameterized with $vendorId = null instead of a specific
 * vendor) as App\Filament\Vendor\Pages\Analytics shows a vendor for their
 * own store. See App\Support\Analytics\* for the underlying queries and
 * App\Http\Middleware\TrackSiteVisit for how visits are recorded.
 */
class Analytics extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->can('analytics.view') ?? false;
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            AnalyticsStatsWidget::class,
            SalesChartWidget::class,
            VisitorsChartWidget::class,
            TopCountriesWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make($this->getColumns())
                ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
        ]);
    }
}
