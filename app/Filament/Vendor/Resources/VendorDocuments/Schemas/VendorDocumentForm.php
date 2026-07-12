<?php

namespace App\Filament\Vendor\Resources\VendorDocuments\Schemas;

use App\Enums\VendorDocumentType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class VendorDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(VendorDocumentType::class)
                    ->required(),
                FileUpload::make('file_path')
                    ->label('File')
                    ->disk('local')
                    ->directory('vendor-documents')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(5120)
                    ->required(),
            ]);
    }
}
