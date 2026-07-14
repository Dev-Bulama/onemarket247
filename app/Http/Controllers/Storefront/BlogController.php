<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::published()
            ->latest('published_at')
            ->paginate(9);

        return view('storefront.blog.index', ['posts' => $posts]);
    }

    public function show(BlogPost $post): View
    {
        abort_unless($post->status->value === 'published' && $post->published_at?->isPast(), 404);

        $recent = BlogPost::published()
            ->whereKeyNot($post->id)
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('storefront.blog.show', ['post' => $post, 'recent' => $recent]);
    }
}
