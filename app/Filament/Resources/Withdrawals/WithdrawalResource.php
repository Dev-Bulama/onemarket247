<?php

namespace App\Filament\Resources\Withdrawals;

use App\Filament\Resources\Withdrawals\Pages\ListWithdrawals;
use App\Filament\Resources\Withdrawals\Pages\ViewWithdrawal;
use App\Filament\Resources\Withdrawals\Tables\WithdrawalsTable;
use App\Models\Withdrawal;
use App\Support\PriceDisplay;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only + action-driven, like OrderResource/PaymentResource: a
 * withdrawal only ever changes through App\Actions\Withdrawal\*
 * (approve/reject/mark-paid), never a free-form edit form.
 */
class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|\UnitEnum|null $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return WithdrawalsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Withdrawal')
                ->columns(3)
                ->schema([
                    TextEntry::make('vendor.business_name')->label('Vendor'),
                    TextEntry::make('amount')->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('withdrawalMethod.bank_name')->label('Bank')->placeholder('—'),
                    TextEntry::make('reviewer.name')->label('Reviewed by')->placeholder('—'),
                    TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                    TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                    TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWithdrawals::route('/'),
            'view' => ViewWithdrawal::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('withdrawals.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
