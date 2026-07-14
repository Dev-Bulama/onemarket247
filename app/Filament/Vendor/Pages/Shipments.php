<?php

namespace App\Filament\Vendor\Pages;

use App\Actions\Shipping\CreateShipmentAction;
use App\Actions\Shipping\RecordShipmentEventAction;
use App\Enums\ShipmentStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\PickupStation;
use App\Models\Shipment;
use App\Models\ShippingCarrier;
use App\Models\VendorOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * "/vendor/shipments" per docs/architecture/07-vendor-dashboard.md: assign
 * carrier/tracking number. Shipment queries never need an explicit
 * vendor_id filter — App\Models\Scopes\BelongsToVendorScope already scopes
 * the vendorOrder() relationship's own query whenever it's touched under
 * the vendor guard, and whereHas('vendorOrder') triggers that subquery.
 */
class Shipments extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.vendor.pages.shipments';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 7;

    public static function canAccess(): bool
    {
        $user = Auth::guard('vendor')->user();

        return $user->vendor !== null || $user->can('store.orders.fulfil');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createShipment')
                ->label('Create shipment')
                ->schema([
                    Select::make('vendor_order_id')
                        ->label('Order')
                        ->options(fn () => VendorOrder::where('status', VendorOrderStatus::ReadyForPickup)
                            ->pluck('vendor_order_number', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('shipping_carrier_id')
                        ->label('Carrier')
                        ->options(fn () => ShippingCarrier::where('is_active', true)->pluck('name', 'id')),
                    TextInput::make('tracking_number'),
                    Select::make('pickup_station_id')
                        ->label('Pickup station')
                        ->options(fn () => PickupStation::where('is_active', true)->pluck('name', 'id')),
                    DateTimePicker::make('estimated_delivery_at')
                        ->label('Estimated delivery'),
                ])
                ->action(function (array $data) {
                    $vendorOrder = VendorOrder::findOrFail($data['vendor_order_id']);
                    $carrier = $data['shipping_carrier_id'] ? ShippingCarrier::find($data['shipping_carrier_id']) : null;
                    $pickupStation = $data['pickup_station_id'] ? PickupStation::find($data['pickup_station_id']) : null;

                    try {
                        app(CreateShipmentAction::class)->handle(
                            $vendorOrder,
                            $carrier,
                            $data['tracking_number'] ?: null,
                            $pickupStation,
                            $data['estimated_delivery_at'] ?? null,
                            Auth::guard('vendor')->user(),
                        );
                        Notification::make()->title('Shipment created')->success()->send();
                    } catch (InvalidOrderTransitionException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Shipment::query()->whereHas('vendorOrder'))
            ->columns([
                TextColumn::make('vendorOrder.vendor_order_number')
                    ->label('Order'),
                TextColumn::make('carrier.name')
                    ->label('Carrier')
                    ->placeholder('—'),
                TextColumn::make('tracking_number')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('shipped_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('estimated_delivery_at')
                    ->label('ETA')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('delivered_at')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('recordEvent')
                    ->label('Record event')
                    ->visible(fn (Shipment $record) => ! in_array($record->status, [ShipmentStatus::Delivered, ShipmentStatus::Returned], true))
                    ->schema([
                        Select::make('status')
                            ->options(ShipmentStatus::class)
                            ->required(),
                        TextInput::make('location'),
                        Textarea::make('description'),
                    ])
                    ->action(function (Shipment $record, array $data) {
                        try {
                            app(RecordShipmentEventAction::class)->handle(
                                $record,
                                $data['status'],
                                $data['location'] ?: null,
                                $data['description'] ?: null,
                                Auth::guard('vendor')->user(),
                            );
                            Notification::make()->title('Shipment event recorded')->success()->send();
                        } catch (InvalidOrderTransitionException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
