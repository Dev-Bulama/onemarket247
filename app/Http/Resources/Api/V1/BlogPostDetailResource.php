<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full post-detail shape, mirroring resources/views/storefront/blog/show.blade.php.
 */
class BlogPostDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'cover_image' => $this->getFirstMediaUrl('cover') ?: null,
            'author_name' => $this->whenLoaded('author', fn () => $this->author?->name),
            'published_at' => $this->published_at,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
        ];
    }
}
