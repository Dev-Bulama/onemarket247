<?php

namespace App\Filament\Vendor\Pages;

use App\Actions\Withdrawal\CancelWithdrawalAction;
use App\Actions\Withdrawal\RequestWithdrawalAction;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Models\Vendor;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use App\Support\PriceDisplay;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * "/vendor/withdrawals" per docs/architecture/07-vendor-dashboard.md:
 * request/view status. Approve/reject/mark-paid remain admin-only actions
 * on App\Filament\Resources\Withdrawals\WithdrawalResource.
 */
class Withdrawals extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.vendor.pages.withdrawals';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        $user = Auth::guard('vendor')->user();

        return $user->vendor !== null || $user->can('store.withdrawals.request');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addMethod')
                ->label('Add bank account')
                ->schema([
                    TextInput::make('bank_name')->required(),
                    TextInput::make('account_name')->required(),
                    TextInput::make('account_number')->required(),
                    Toggle::make('is_default')->default(true),
                ])
                ->action(function (array $data) {
                    WithdrawalMethod::create([
                        'vendor_id' => $this->vendorId(),
                        'bank_name' => $data['bank_name'],
                        'account_name' => $data['account_name'],
                        'account_number' => $data['account_number'],
                        'is_default' => $data['is_default'],
                    ]);

                    Notification::make()->title('Bank account added')->success()->send();
                }),

            Action::make('request')
                ->label('Request withdrawal')
                ->color('success')
                ->schema([
                    Select::make('withdrawal_method_id')
                        ->label('Bank account')
                        ->options(fn () => WithdrawalMethod::where('vendor_id', $this->vendorId())->pluck('bank_name', 'id'))
                        ->required(),
                    TextInput::make('amount')
                        ->label('Amount (USD)')
                        ->numeric()
                        ->required()
                        ->minValue(0.01),
                ])
                ->action(function (array $data) {
                    $vendor = Vendor::findOrFail($this->vendorId());
                    $method = WithdrawalMethod::findOrFail($data['withdrawal_method_id']);

                    try {
                        app(RequestWithdrawalAction::class)->handle($vendor, $method, (int) round($data['amount'] * 100));
                        Notification::make()->title('Withdrawal requested')->success()->send();
                    } catch (InsufficientWalletBalanceException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Withdrawal::where('vendor_id', $this->vendorId()))
            ->columns([
                TextColumn::make('amount')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                TextColumn::make('withdrawalMethod.bank_name')
                    ->label('Bank'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('rejection_reason')
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('cancel')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Withdrawal $record) => $record->status === WithdrawalStatus::Pending)
                    ->action(function (Withdrawal $record) {
                        app(CancelWithdrawalAction::class)->handle($record);
                        Notification::make()->title('Withdrawal cancelled')->success()->send();
                    }),
            ]);
    }

    private function vendorId(): ?int
    {
        return Auth::guard('vendor')->user()->actingVendorId();
    }
}
