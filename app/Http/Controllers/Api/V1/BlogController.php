<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BlogPostDetailResource;
use App\Http\Resources\Api\V1\BlogPostResource;
use App\Models\BlogPost;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;

/**
 * Mirrors Storefront\BlogController exactly — same published() scope,
 * same "not yet published" 404 behaviour — so mobile and web can never
 * show different posts.
 */
class BlogController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = BlogPost::published()
            ->with('author')
            ->latest('published_at')
            ->paginate(9);

        return Paginated::response($posts, BlogPostResource::class);
    }

    public function show(BlogPost $post): JsonResponse
    {
        abort_unless($post->status->value === 'published' && $post->published_at?->isPast(), 404);

        return ApiResponse::success(new BlogPostDetailResource($post->load('author')));
    }
}
