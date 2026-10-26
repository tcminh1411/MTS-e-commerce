<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Xử lý các yêu cầu liên quan đến bài viết. */
class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with('category');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('post_category_id', $request->input('category'));
        }

        $posts = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();
        $categories = PostCategory::ordered()->get();

        return view('admin.posts.index', compact('posts', 'categories'));
    }

    public function create()
    {
        $categories = PostCategory::ordered()->get();

        return view('admin.posts.create', [
            'post' => new Post,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['slug'] = empty($data['slug'])
            ? Post::slugify($data['title'])
            : Post::slugify($data['slug']);

        $data['thumbnail'] = $this->resolveThumbnail($request, null);
        $data['published_at'] = $this->resolvePublishedAt($request, $data['status'], null);

        Post::create($data);

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Đã tạo bài viết "'.$data['title'].'".');
    }

    public function edit(Post $post)
    {
        $categories = PostCategory::ordered()->get();

        return view('admin.posts.edit', compact('post', 'categories'));
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validateData($request, $post->id);

        $data['slug'] = empty($data['slug'])
            ? Post::slugify($data['title'], $post->id)
            : Post::slugify($data['slug'], $post->id);

        $data['thumbnail'] = $this->resolveThumbnail($request, $post->thumbnail);
        $data['published_at'] = $this->resolvePublishedAt($request, $data['status'], $post->published_at);

        $post->update($data);

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Đã cập nhật bài viết "'.$post->title.'".');
    }

    public function destroy(Post $post)
    {
        $title = $post->title;

        // Xoá thumbnail local (nếu có)
        if ($post->thumbnail && ! str_starts_with($post->thumbnail, 'http')) {
            Storage::disk('public')->delete($post->thumbnail);
        }

        $post->delete();

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Đã xoá bài viết "'.$title.'".');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('posts', 'slug')->ignore($ignoreId),
            ],
            'post_category_id' => ['nullable', 'integer', 'exists:post_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:200000'],
            'thumbnail_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'thumbnail_url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', Rule::in(array_keys(Post::STATUSES))],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ], [
            'slug.regex' => 'Slug chỉ được chứa chữ thường, số và dấu gạch ngang.',
            'slug.unique' => 'Slug này đã được dùng cho bài viết khác.',
            'thumbnail_file.image' => 'File thumbnail phải là ảnh.',
            'thumbnail_file.max' => 'Ảnh không được vượt quá 2MB.',
            'thumbnail_url.url' => 'URL thumbnail không hợp lệ.',
        ]);

        unset($data['thumbnail_file'], $data['thumbnail_url']);

        return $data;
    }

    /**
     * 3 case:
     *  1. Upload file → xoá file cũ (nếu local), lưu file mới → trả path
     *  2. Nhập URL → xoá file cũ (nếu local), trả URL
     *  3. Không nhập gì → giữ ảnh cũ
     */
    private function resolveThumbnail(Request $request, ?string $oldThumbnail): ?string
    {
        if ($request->hasFile('thumbnail_file')) {
            $this->deleteLocalFile($oldThumbnail);

            return $request->file('thumbnail_file')->store('posts', 'public');
        }

        if ($request->filled('thumbnail_url')) {
            $this->deleteLocalFile($oldThumbnail);

            return $request->input('thumbnail_url');
        }

        return $oldThumbnail;
    }

    private function deleteLocalFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Logic:
     *  - Nếu status = published:
     *      + Nếu input có published_at → dùng input
     *      + Nếu không → giữ old; nếu old cũng null → now()
     *  - Nếu status = draft → published_at = null
     */
    private function resolvePublishedAt(Request $request, string $status, $oldValue): ?string
    {
        if ($status === Post::STATUS_DRAFT) {
            return null;
        }

        if ($request->filled('published_at')) {
            return $request->input('published_at');
        }

        if ($oldValue) {
            return is_string($oldValue) ? $oldValue : $oldValue->toDateTimeString();
        }

        return now()->toDateTimeString();
    }
}
