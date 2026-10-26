<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;

/** Xử lý các yêu cầu liên quan đến bài viết blog. */
class BlogController extends Controller
{
    public function index(Request $request)
    {
        $categories = PostCategory::active()->ordered()->get();

        $query = Post::with(['category'])->published();

        // Filter theo category slug
        if ($request->filled('category')) {
            $catSlug = $request->input('category');
            $query->whereHas('category', function ($q) use ($catSlug) {
                $q->where('slug', $catSlug)->where('is_active', true);
            });
        }

        // Search
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('excerpt', 'like', "%{$q}%");
            });
        }

        $posts = $query->ordered()->paginate(9)->withQueryString();

        return view('shop.blog.index', compact('posts', 'categories'));
    }

    public function show(Post $post)
    {
        // Chỉ cho xem bài đã published
        abort_unless(
            $post->status === Post::STATUS_PUBLISHED && $post->published_at && $post->published_at <= now(),
            404
        );

        // Tăng view_count (atomic increment, tránh race condition)
        $post->increment('view_count');

        // Bài viết liên quan (cùng category, trừ bài hiện tại, tối đa 3)
        $related = Post::published()
            ->when($post->post_category_id, fn ($q) => $q->where('post_category_id', $post->post_category_id))
            ->where('id', '!=', $post->id)
            ->ordered()
            ->limit(3)
            ->get();

        return view('shop.blog.show', compact('post', 'related'));
    }
}
