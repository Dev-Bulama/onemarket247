<?php

namespace App\Filament\Widgets;

use App\Enums\UserType;
use App\Enums\VendorStatus;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Customers', User::where('user_type', UserType::Customer)->count())
                ->description('Registered customer accounts')
                ->icon('heroicon-o-users'),
            Stat::make('Active Vendors', Vendor::where('status', VendorStatus::Approved)->count())
                ->description('Approved vendors')
                ->icon('heroicon-o-building-storefront')
                ->color('success'),
            Stat::make('Pending Vendors', Vendor::whereIn('status', [VendorStatus::Pending, VendorStatus::UnderReview])->count())
                ->description('Awaiting approval')
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Stores', Store::count())
                ->description('Total storefronts')
                ->icon('heroicon-o-shopping-bag'),
        ];
    }
}
