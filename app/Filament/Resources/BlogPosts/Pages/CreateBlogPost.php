<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Concerns\HandlesStagedSingleImage;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Models\BlogPost;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateBlogPost extends CreateRecord
{
    use HandlesStagedSingleImage;

    protected static string $resource = BlogPostResource::class;

    private ?string $stagedImage = null;

    protected function handleRecordCreation(array $data): Model
    {
        $this->stagedImage = $data['cover'] ?? null;
        unset($data['cover']);

        $data['author_id'] = Auth::guard('admin')->id();

        return BlogPost::create($data);
    }

    protected function afterCreate(): void
    {
        $this->attachStagedImage($this->record, $this->stagedImage, 'cover');
    }
}
