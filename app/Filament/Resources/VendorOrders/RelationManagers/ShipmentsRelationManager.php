<?php

namespace App\Filament\Resources\VendorOrders\RelationManagers;

use App\Actions\Delivery\CreateDeliveryRequestAction;
use App\Actions\Shipping\CreateShipmentAction;
use App\Actions\Shipping\RecordShipmentEventAction;
use App\Enums\ShipmentStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\ShipmentAlreadyAssignedException;
use App\Models\PickupStation;
use App\Models\Shipment;
use App\Models\ShippingCarrier;
use App\Models\VendorOrder;
use App\Support\Filament\MinorUnitsInput;
use App\Support\PriceDisplay;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Creating a shipment here also advances the vendor order to Shipped (see
 * App\Actions\Shipping\CreateShipmentAction) — this replaces the generic
 * "Mark as Shipped" transition action that ViewVendorOrder deliberately
 * omits once a shipment carries real carrier/tracking data.
 */
class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
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
                TextColumn::make('events_count')
                    ->label('Events')
                    ->counts('events'),
                TextColumn::make('deliveryRequest.status')
                    ->label('Delivery')
                    ->badge()
                    ->formatStateUsing(fn (?Shipment $record) => $record?->deliveryAssignment?->deliveryPartner?->full_name
                        ?? $record?->deliveryRequest?->status?->getLabel()
                        ?? 'Not requested')
                    ->placeholder('Not requested'),
            ])
            ->headerActions([
                Action::make('createShipment')
                    ->label('Create shipment')
                    ->visible(fn (RelationManager $livewire) => $livewire->getOwnerRecord()->status === VendorOrderStatus::ReadyForPickup)
                    ->schema([
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
                    ->action(function (array $data, RelationManager $livewire) {
                        /** @var VendorOrder $vendorOrder */
                        $vendorOrder = $livewire->getOwnerRecord();
                        $carrier = $data['shipping_carrier_id'] ? ShippingCarrier::find($data['shipping_carrier_id']) : null;
                        $pickupStation = $data['pickup_station_id'] ? PickupStation::find($data['pickup_station_id']) : null;

                        try {
                            app(CreateShipmentAction::class)->handle(
                                $vendorOrder,
                                $carrier,
                                $data['tracking_number'] ?: null,
                                $pickupStation,
                                $data['estimated_delivery_at'] ?? null,
                                auth()->user(),
                            );
                            Notification::make()->title('Shipment created')->success()->send();
                        } catch (InvalidOrderTransitionException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
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
                                auth()->user(),
                            );
                            Notification::make()->title('Shipment event recorded')->success()->send();
                        } catch (InvalidOrderTransitionException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('requestDeliveryPartner')
                    ->label('Request delivery partner')
                    ->color('info')
                    ->visible(fn (Shipment $record) => $record->deliveryAssignment === null && $record->deliveryRequest === null)
                    ->schema(fn (Shipment $record) => [
                        TextInput::make('delivery_fee')
                            ->label('Delivery fee')
                            ->numeric()
                            ->prefix(PriceDisplay::baseCurrencyCode())
                            ->default($record->vendorOrder->shipping_amount / 100)
                            ->afterStateHydrated(MinorUnitsInput::hydrate())
                            ->dehydrateStateUsing(MinorUnitsInput::dehydrate())
                            ->required(),
                        DateTimePicker::make('required_by')
                            ->label('Required by')
                            ->default($record->estimated_delivery_at),
                        Textarea::make('special_instructions')
                            ->label('Instructions for the partner'),
                    ])
                    ->action(function (Shipment $record, array $data) {
                        try {
                            app(CreateDeliveryRequestAction::class)->handle(
                                $record,
                                $data['delivery_fee'],
                                $data['required_by'] ?? null,
                                $data['special_instructions'] ?: null,
                                auth()->user(),
                            );
                            Notification::make()->title('Delivery partners notified')->success()->send();
                        } catch (ShipmentAlreadyAssignedException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
