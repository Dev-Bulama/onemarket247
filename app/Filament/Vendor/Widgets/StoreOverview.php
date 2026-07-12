<?php

namespace App\Filament\Vendor\Widgets;

use App\Models\Vendor;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StoreOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $vendorId = Auth::guard('vendor')->user()->actingVendorId();
        $vendor = $vendorId ? Vendor::find($vendorId) : null;
        $store = $vendor?->store;

        return [
            Stat::make('Business', $vendor?->business_name ?? '—')
                ->description($store?->name ?? 'No store yet'),
            Stat::make('Vendor status', $vendor?->status->getLabel() ?? '—')
                ->description($vendor?->is_verified ? 'Verified' : 'Not yet verified')
                ->color($vendor?->is_verified ? 'success' : 'gray'),
            Stat::make('Subscription plan', $vendor?->currentSubscription()?->plan?->name ?? 'None')
                ->description('Current billing plan'),
        ];
    }
}
