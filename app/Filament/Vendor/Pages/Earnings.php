<?php

namespace App\Filament\Vendor\Pages;

use App\Models\VendorWallet;
use App\Models\VendorWalletTransaction;
use App\Support\PriceDisplay;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * "/vendor/earnings" per docs/architecture/07-vendor-dashboard.md: commission
 * breakdown + wallet balance. Read-only — balances only ever move through
 * App\Actions\Wallet\* actions triggered elsewhere (checkout, order
 * completion, refunds, withdrawals).
 */
class Earnings extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.vendor.pages.earnings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        $user = Auth::guard('vendor')->user();

        return $user->vendor !== null || $user->can('store.reports.view');
    }

    public function getWallet(): VendorWallet
    {
        return VendorWallet::firstOrCreate(['vendor_id' => $this->vendorId()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(VendorWalletTransaction::query()->whereHas('wallet', fn (Builder $query) => $query->where('vendor_id', $this->vendorId())))
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('balance_bucket')
                    ->badge(),
                TextColumn::make('amount')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100)
                    ->color(fn (int $state) => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('vendorOrder.vendor_order_number')
                    ->label('Order')
                    ->placeholder('—'),
                TextColumn::make('reason')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function vendorId(): ?int
    {
        return Auth::guard('vendor')->user()->actingVendorId();
    }
}
