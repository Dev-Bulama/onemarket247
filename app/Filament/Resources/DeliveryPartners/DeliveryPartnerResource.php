<?php

namespace App\Filament\Resources\DeliveryPartners;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\DeliveryPartners\Pages\CreateDeliveryPartner;
use App\Filament\Resources\DeliveryPartners\Pages\EditDeliveryPartner;
use App\Filament\Resources\DeliveryPartners\Pages\ListDeliveryPartners;
use App\Filament\Resources\DeliveryPartners\Schemas\DeliveryPartnerForm;
use App\Filament\Resources\DeliveryPartners\Tables\DeliveryPartnersTable;
use App\Models\DeliveryPartner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The registered courier/rider roster admins manage — see
 * App\Models\DeliveryPartner's own docblock for why this is a roster
 * entity with no login, unlike Vendor.
 */
class DeliveryPartnerResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'delivery_partners.manage';

    protected static ?string $model = DeliveryPartner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipping & Delivery';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return DeliveryPartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryPartnersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryPartners::route('/'),
            'create' => CreateDeliveryPartner::route('/create'),
            'edit' => EditDeliveryPartner::route('/{record}/edit'),
        ];
    }
}
