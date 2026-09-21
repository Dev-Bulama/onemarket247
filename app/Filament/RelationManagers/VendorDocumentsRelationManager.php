<?php

namespace App\Filament\RelationManagers;

use App\Actions\Vendor\ReviewVendorDocumentAction;
use App\Enums\VendorDocumentStatus;
use App\Models\VendorDocument;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Per-document approve/reject, shared with App\Filament\Resources\Vendors'
 * identical relation manager (same VendorDocument model, same relation
 * name "documents" on both VendorApplication and Vendor) — see
 * App\Actions\Vendor\ReviewVendorDocumentAction.
 */
class VendorDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('file_path')
                    ->label('Document')
                    ->formatStateUsing(fn () => 'Open document')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (VendorDocument $record) => route('vendor-documents.download', $record))
                    ->openUrlInNewTab(),
                TextColumn::make('rejection_reason')->placeholder('—')->limit(40),
                TextColumn::make('verifier.name')->label('Reviewed by')->placeholder('—'),
                TextColumn::make('verified_at')->dateTime()->placeholder('—'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (VendorDocument $record) => auth()->user()?->can('vendors.approve')
                        && $record->status !== VendorDocumentStatus::Verified)
                    ->action(function (VendorDocument $record) {
                        app(ReviewVendorDocumentAction::class)->approve($record, auth()->user());
                        Notification::make()->title('Document verified')->success()->send();
                    }),
                Action::make('reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason (shown to the applicant/vendor)')
                            ->required(),
                    ])
                    ->visible(fn (VendorDocument $record) => auth()->user()?->can('vendors.approve')
                        && $record->status !== VendorDocumentStatus::Rejected)
                    ->action(function (VendorDocument $record, array $data) {
                        app(ReviewVendorDocumentAction::class)->reject($record, $data['reason'], auth()->user());
                        Notification::make()->title('Document rejected')->success()->send();
                    }),
            ]);
    }
}
