<?php

namespace App\Filament\Vendor\Widgets\Analytics;

use App\Support\Analytics\VisitorAnalytics;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TopCountriesWidget extends ChartWidget
{
    protected ?string $heading = 'Top Countries by Visits';

    public ?string $filter = '30';

    public static function canView(): bool
    {
        $user = Auth::guard('vendor')->user();

        return $user->vendor !== null || $user->can('store.reports.view');
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
        ];
    }

    protected function getData(): array
    {
        $vendorId = Auth::guard('vendor')->user()->actingVendorId();
        $days = (int) ($this->filter ?? 30);
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $countries = app(VisitorAnalytics::class)->topCountries($vendorId, $from, $to);

        return [
            'datasets' => [
                [
                    'label' => 'Visits',
                    'data' => $countries->pluck('visits')->all(),
                ],
            ],
            'labels' => $countries->pluck('country_name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
