<?php

namespace App\Filament\Resources\VendorApplications;

use App\Filament\Resources\VendorApplications\Pages\ListVendorApplications;
use App\Filament\Resources\VendorApplications\Pages\ViewVendorApplication;
use App\Filament\Resources\VendorApplications\Tables\VendorApplicationsTable;
use App\Models\VendorApplication;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only + action-driven, like AuditLogResource: applications are never
 * created or free-form edited by admins, only reviewed (approve/reject) via
 * table/page actions — see App\Actions\Vendor\{Approve,Reject}VendorApplicationAction.
 */
class VendorApplicationResource extends Resource
{
    protected static ?string $model = VendorApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Vendor Management';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return VendorApplicationsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Applicant')
                ->columns(3)
                ->schema([
                    TextEntry::make('full_name'),
                    TextEntry::make('email'),
                    TextEntry::make('phone')->placeholder('—'),
                ]),
            Section::make('Business')
                ->columns(3)
                ->schema([
                    TextEntry::make('business_name'),
                    TextEntry::make('registration_number')->placeholder('—'),
                    TextEntry::make('tax_identification_number')->placeholder('—'),
                ]),
            Section::make('Store')
                ->columns(2)
                ->schema([
                    TextEntry::make('store_name'),
                    TextEntry::make('store_category')->placeholder('—'),
                    TextEntry::make('store_description')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('address')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Banking')
                ->columns(3)
                ->schema([
                    TextEntry::make('bank_name'),
                    TextEntry::make('bank_account_name'),
                    TextEntry::make('bank_account_number'),
                ]),
            Section::make('Documents')
                ->schema([
                    TextEntry::make('documents.type')
                        ->badge()
                        ->listWithLineBreaks()
                        ->placeholder('No documents uploaded'),
                ]),
            Section::make('Review')
                ->columns(2)
                ->schema([
                    TextEntry::make('status')->badge(),
                    TextEntry::make('subscriptionPlan.name')->label('Requested plan')->placeholder('Default plan'),
                    TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorApplications::route('/'),
            'view' => ViewVendorApplication::route('/{record}'),
        ];
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
