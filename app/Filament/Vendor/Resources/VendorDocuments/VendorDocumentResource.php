<?php

namespace App\Filament\Vendor\Resources\VendorDocuments;

use App\Filament\Vendor\Resources\VendorDocuments\Pages\CreateVendorDocument;
use App\Filament\Vendor\Resources\VendorDocuments\Pages\ListVendorDocuments;
use App\Filament\Vendor\Resources\VendorDocuments\Schemas\VendorDocumentForm;
use App\Filament\Vendor\Resources\VendorDocuments\Tables\VendorDocumentsTable;
use App\Models\VendorDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Uploads are immutable once submitted (see VendorDocument's insert-once
 * role in the onboarding flow) — a vendor "resubmits" by uploading a new
 * document of the same type rather than editing an existing row, and
 * verification/rejection is admin-only (App\Filament\Resources\VendorApplications).
 */
class VendorDocumentResource extends Resource
{
    protected static ?string $model = VendorDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return VendorDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorDocumentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorDocuments::route('/'),
            'create' => CreateVendorDocument::route('/create'),
        ];
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
