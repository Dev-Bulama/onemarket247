<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Actions\Order\CancelVendorOrderAction;
use App\Actions\Order\UpdateVendorOrderStatusAction;
use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Filament\Resources\VendorOrders\VendorOrderResource;
use App\Models\VendorOrder;
use App\Support\PriceDisplay;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Lets an admin advance/cancel a vendor's own sub-order status straight
 * from the platform-wide Order page, without navigating into
 * VendorOrderResource — same underlying action
 * (App\Actions\Order\UpdateVendorOrderStatusAction) as
 * VendorOrderResource\Pages\ViewVendorOrder, so the change is
 * automatically the vendor's own VendorOrder row: it shows up on their
 * dashboard immediately, recomputes the aggregated Order status the
 * customer sees, and writes the same status-history audit trail —
 * nothing here is a separate/duplicated status machine.
 *
 * Shipped/OutForDelivery/Delivered are excluded for the same reason as
 * ViewVendorOrder: those carry real carrier/tracking data and belong on
 * the vendor order's own Shipments relation manager instead of a bare
 * status flip.
 */
class VendorOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorOrders';

    private const array SHIPMENT_MANAGED = [
        VendorOrderStatus::Shipped,
        VendorOrderStatus::OutForDelivery,
        VendorOrderStatus::Delivered,
    ];

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('vendor_order_number')
            ->columns([
                TextColumn::make('vendor_order_number')
                    ->label('Sub-order'),
                TextColumn::make('vendor.business_name')
                    ->label('Vendor'),
                TextColumn::make('order_items_count')
                    ->label('Items')
                    ->counts('orderItems'),
                TextColumn::make('total')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->headerActions([])
            ->recordActions([
                ...$this->transitionActions(),
                Action::make('cancel')
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')->required()->maxLength(500),
                    ])
                    ->visible(fn (VendorOrder $record) => in_array($record->status, CancelVendorOrderAction::CANCELLABLE_FROM, true))
                    ->action(function (VendorOrder $record, array $data) {
                        try {
                            app(CancelVendorOrderAction::class)->handle($record, $data['reason'], auth()->user());
                            Notification::make()->title('Vendor order cancelled')->success()->send();
                        } catch (InvalidOrderTransitionException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('view')
                    ->url(fn (VendorOrder $record) => VendorOrderResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }

    /**
     * One action per possible non-shipment target status; each is only
     * visible on a row currently allowed to move to that target (see
     * UpdateVendorOrderStatusAction::ALLOWED_TRANSITIONS) — mirrors
     * ViewVendorOrder::transitionActions(), just evaluated per-row instead
     * of for a single record.
     *
     * @return array<int, Action>
     */
    private function transitionActions(): array
    {
        $allTargets = array_merge(...array_values(UpdateVendorOrderStatusAction::ALLOWED_TRANSITIONS));
        $uniqueTargetValues = array_unique(array_map(fn (VendorOrderStatus $status) => $status->value, $allTargets));

        $targets = array_values(array_filter(
            array_map(fn (string $value) => VendorOrderStatus::from($value), $uniqueTargetValues),
            fn (VendorOrderStatus $status) => ! in_array($status, self::SHIPMENT_MANAGED, true)
        ));

        return array_map(
            fn (VendorOrderStatus $target) => Action::make('transition-'.$target->value)
                ->label('Mark as '.$target->getLabel())
                ->color('success')
                ->schema([
                    Textarea::make('note')->label('Note (optional)')->maxLength(500),
                ])
                ->visible(fn (VendorOrder $record) => in_array($target, UpdateVendorOrderStatusAction::nextStatusesFor($record->status), true))
                ->action(function (VendorOrder $record, array $data) use ($target) {
                    try {
                        app(UpdateVendorOrderStatusAction::class)->handle($record, $target, $data['note'] ?: null, auth()->user());
                        Notification::make()->title('Status updated to '.$target->getLabel())->success()->send();
                    } catch (InvalidOrderTransitionException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            $targets
        );
    }
}
