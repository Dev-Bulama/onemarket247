<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics\VisitorAnalytics;
use Filament\Widgets\ChartWidget;

class TopCountriesWidget extends ChartWidget
{
    protected ?string $heading = 'Top Countries by Visits';

    public ?string $filter = '30';

    public static function canView(): bool
    {
        return auth()->user()?->can('analytics.view') ?? false;
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
        $days = (int) ($this->filter ?? 30);
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $countries = app(VisitorAnalytics::class)->topCountries(null, $from, $to);

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
