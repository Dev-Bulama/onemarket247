<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics\CustomerAnalytics;
use App\Support\Analytics\SalesAnalytics;
use App\Support\Analytics\VisitorAnalytics;
use App\Support\PriceDisplay;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Headline sales/customer/visitor numbers for the trailing 30 days.
 * Unlike the chart widgets below, StatsOverviewWidget has no native period
 * filter, so this stays a fixed rolling window for simplicity — use the
 * chart widgets to look at a different range.
 */
class AnalyticsStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('analytics.view') ?? false;
    }

    protected function getStats(): array
    {
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        $sales = app(SalesAnalytics::class)->summary(null, $from, $to);
        $customerAnalytics = app(CustomerAnalytics::class);
        $visitors = app(VisitorAnalytics::class)->summary(null, $from, $to);

        return [
            Stat::make('Revenue (30 days)', PriceDisplay::format($sales['revenue']))
                ->description($sales['orders'].' orders')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('Average Order Value', PriceDisplay::format($sales['average_order_value']))
                ->icon('heroicon-o-shopping-cart'),
            Stat::make('Active Customers', $customerAnalytics->activeCustomers(null, $from, $to))
                ->description($customerAnalytics->newCustomers(null, $from, $to).' new')
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make('Site Visits', $visitors['visits'])
                ->description($visitors['unique_visitors'].' unique visitors')
                ->icon('heroicon-o-eye'),
        ];
    }
}
