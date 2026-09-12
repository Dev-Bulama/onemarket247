<?php

namespace App\Filament\Vendor\Pages;

use App\Filament\Vendor\Widgets\Analytics\AnalyticsStatsWidget;
use App\Filament\Vendor\Widgets\Analytics\SalesChartWidget;
use App\Filament\Vendor\Widgets\Analytics\TopCountriesWidget;
use App\Filament\Vendor\Widgets\Analytics\VisitorsChartWidget;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * This vendor's own sales, customer, and store-visitor analytics — the
 * vendor-scoped mirror of App\Filament\Pages\Analytics (admin). See
 * App\Support\Analytics\* for the underlying queries.
 */
class Analytics extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = Auth::guard('vendor')->user();

        return $user->vendor !== null || $user->can('store.reports.view');
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
