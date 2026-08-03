<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\PostCategory;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `/blog` and `/blog/{slug}` — see docs/modules/M10-public-site-cms.md's
 * "Posts (blog)".
 */
class PostController extends Controller
{
    public function index(Request $request): View
    {
        $query = Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');

        $category = $request->string('category')->toString();

        if ($category !== '' && PostCategory::tryFrom($category)) {
            $query->where('category', $category);
        }

        $posts = $query->paginate(9)->withQueryString();

        return view('public.blog.index', [
            'posts' => $posts,
            'categories' => PostCategory::cases(),
            'activeCategory' => $category ?: null,
        ]);
    }

    public function show(string $slug): View
    {
        $post = Post::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('author')
            ->first();

        if (! $post) {
            throw new NotFoundHttpException;
        }

        $post->increment('view_count');

        $related = Post::query()
            ->where('id', '!=', $post->id)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('category', $post->category?->value)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('public.blog.show', ['post' => $post, 'related' => $related]);
    }
}
