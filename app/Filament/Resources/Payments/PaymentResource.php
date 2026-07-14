<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\RelationManagers\LogsRelationManager;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only + action-driven, like OrderResource: a payment only ever
 * changes through App\Actions\Payment\* (initialize/verify/refund), never
 * a free-form edit form. The refund action lives on ViewPayment's header,
 * gated on refunds.manage separately from payments.view/payments.manage
 * per docs/architecture/03-modules-and-roles.md's permission list.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment')
                ->columns(3)
                ->schema([
                    TextEntry::make('order.order_number')->label('Order'),
                    TextEntry::make('reference'),
                    TextEntry::make('gateway')->placeholder('—'),
                    TextEntry::make('gateway_reference')->label('Gateway reference')->placeholder('—'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                    TextEntry::make('failed_at')->dateTime()->placeholder('—'),
                ]),
            Section::make('Amounts')
                ->columns(2)
                ->schema([
                    TextEntry::make('amount')->money('USD', divideBy: 100),
                    TextEntry::make('refunded_amount')->label('Refunded')->money('USD', divideBy: 100),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'view' => ViewPayment::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('payments.view') ?? false;
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
