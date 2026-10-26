<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Xử lý các yêu cầu liên quan đến danh mục bài viết. */
class PostCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = PostCategory::withCount('posts');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $categories = $query->ordered()->paginate(15)->withQueryString();

        return view('admin.post_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.post_categories.create', ['category' => new PostCategory]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['slug'] = empty($data['slug'])
            ? PostCategory::slugify($data['name'])
            : PostCategory::slugify($data['slug']);

        PostCategory::create($data);

        return redirect()
            ->route('admin.post_categories.index')
            ->with('success', 'Đã tạo danh mục "'.$data['name'].'".');
    }

    public function edit(PostCategory $post_category)
    {
        return view('admin.post_categories.edit', ['category' => $post_category]);
    }

    public function update(Request $request, PostCategory $post_category)
    {
        $data = $this->validateData($request, $post_category->id);

        $data['slug'] = empty($data['slug'])
            ? PostCategory::slugify($data['name'], $post_category->id)
            : PostCategory::slugify($data['slug'], $post_category->id);

        $post_category->update($data);

        return redirect()
            ->route('admin.post_categories.index')
            ->with('success', 'Đã cập nhật danh mục "'.$post_category->name.'".');
    }

    public function destroy(PostCategory $post_category)
    {
        $name = $post_category->name;

        // FK nullOnDelete — posts sẽ có post_category_id = null
        $post_category->delete();

        return redirect()
            ->route('admin.post_categories.index')
            ->with('success', 'Đã xoá danh mục "'.$name.'". Các bài viết liên quan đã chuyển sang "Chưa phân loại".');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('post_categories', 'slug')->ignore($ignoreId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'slug.regex' => 'Slug chỉ được chứa chữ thường, số và dấu gạch ngang.',
            'slug.unique' => 'Slug này đã được dùng cho danh mục khác.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) $request->input('sort_order', 0);

        return $data;
    }
}
