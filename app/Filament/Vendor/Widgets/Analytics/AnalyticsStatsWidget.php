<?php

namespace App\Filament\Vendor\Widgets\Analytics;

use App\Support\Analytics\CustomerAnalytics;
use App\Support\Analytics\SalesAnalytics;
use App\Support\Analytics\VisitorAnalytics;
use App\Support\PriceDisplay;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Headline sales/customer/visitor numbers for this vendor's store over the
 * trailing 30 days — the vendor-scoped mirror of
 * App\Filament\Widgets\Analytics\AnalyticsStatsWidget.
 */
class AnalyticsStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = Auth::guard('vendor')->user();

        return $user->vendor !== null || $user->can('store.reports.view');
    }

    protected function getStats(): array
    {
        $vendorId = Auth::guard('vendor')->user()->actingVendorId();
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        $sales = app(SalesAnalytics::class)->summary($vendorId, $from, $to);
        $customerAnalytics = app(CustomerAnalytics::class);
        $visitors = app(VisitorAnalytics::class)->summary($vendorId, $from, $to);

        return [
            Stat::make('Revenue (30 days)', PriceDisplay::format($sales['revenue']))
                ->description($sales['orders'].' orders')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('Average Order Value', PriceDisplay::format($sales['average_order_value']))
                ->icon('heroicon-o-shopping-cart'),
            Stat::make('Customers', $customerAnalytics->activeCustomers($vendorId, $from, $to))
                ->description($customerAnalytics->newCustomers($vendorId, $from, $to).' first-time buyers')
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make('Store Visits', $visitors['visits'])
                ->description($visitors['unique_visitors'].' unique visitors')
                ->icon('heroicon-o-eye'),
        ];
    }
}
