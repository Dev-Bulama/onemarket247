<?php

namespace App\Filament\Resources\ProductReviews\Pages;

use App\Actions\Review\ApproveReviewAction;
use App\Actions\Review\RejectReviewAction;
use App\Enums\ReviewStatus;
use App\Filament\Resources\ProductReviews\ProductReviewResource;
use App\Models\ProductReview;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewProductReview extends ViewRecord
{
    protected static string $resource = ProductReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (ProductReview $record) => auth()->user()?->can('moderate', ProductReview::class)
                    && $record->status === ReviewStatus::Pending)
                ->action(function (ProductReview $record) {
                    app(ApproveReviewAction::class)->handle($record, auth()->user());
                    Notification::make()->title('Review approved')->success()->send();
                }),
            Action::make('reject')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->schema([
                    Textarea::make('reason')->required(),
                ])
                ->visible(fn (ProductReview $record) => auth()->user()?->can('moderate', ProductReview::class)
                    && $record->status === ReviewStatus::Pending)
                ->action(function (ProductReview $record, array $data) {
                    app(RejectReviewAction::class)->handle($record, $data['reason'], auth()->user());
                    Notification::make()->title('Review rejected')->success()->send();
                }),
        ];
    }
}
