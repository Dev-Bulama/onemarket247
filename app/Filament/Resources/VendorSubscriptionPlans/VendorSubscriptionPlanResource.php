<?php

namespace App\Filament\Resources\VendorSubscriptionPlans;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\VendorSubscriptionPlans\Pages\CreateVendorSubscriptionPlan;
use App\Filament\Resources\VendorSubscriptionPlans\Pages\EditVendorSubscriptionPlan;
use App\Filament\Resources\VendorSubscriptionPlans\Pages\ListVendorSubscriptionPlans;
use App\Filament\Resources\VendorSubscriptionPlans\Schemas\VendorSubscriptionPlanForm;
use App\Filament\Resources\VendorSubscriptionPlans\Tables\VendorSubscriptionPlansTable;
use App\Models\VendorSubscriptionPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VendorSubscriptionPlanResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'subscription_plans.manage';

    protected static ?string $model = VendorSubscriptionPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Vendor Management';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return VendorSubscriptionPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorSubscriptionPlansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorSubscriptionPlans::route('/'),
            'create' => CreateVendorSubscriptionPlan::route('/create'),
            'edit' => EditVendorSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
