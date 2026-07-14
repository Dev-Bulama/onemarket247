<?php

namespace App\Filament\Resources\CommissionRules;

use App\Filament\Resources\CommissionRules\Pages\CreateCommissionRule;
use App\Filament\Resources\CommissionRules\Pages\EditCommissionRule;
use App\Filament\Resources\CommissionRules\Pages\ListCommissionRules;
use App\Filament\Resources\CommissionRules\Schemas\CommissionRuleForm;
use App\Filament\Resources\CommissionRules\Tables\CommissionRulesTable;
use App\Models\CommissionRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Vendor-level and subscription-plan-level rules here are one of two
 * possible sources for those tiers — see
 * App\Actions\Commission\ResolveCommissionRuleAction's docblock for how
 * this reconciles with the pre-existing vendors.commission_rate /
 * vendor_subscription_plans.commission_rate_override columns.
 */
class CommissionRuleResource extends Resource
{
    protected static ?string $model = CommissionRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return CommissionRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommissionRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommissionRules::route('/'),
            'create' => CreateCommissionRule::route('/create'),
            'edit' => EditCommissionRule::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('commissions.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('commissions.manage') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('commissions.manage') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('commissions.manage') ?? false;
    }
}
