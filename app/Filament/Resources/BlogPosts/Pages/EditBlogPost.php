<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Concerns\HandlesStagedSingleImage;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    use HandlesStagedSingleImage;

    protected static string $resource = BlogPostResource::class;

    private ?string $stagedImage = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->stagedImage = $data['cover'] ?? null;
        unset($data['cover']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->attachStagedImage($this->record, $this->stagedImage, 'cover');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
