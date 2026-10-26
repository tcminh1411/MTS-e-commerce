<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Xử lý các yêu cầu liên quan đến danh mục sản phẩm. */
class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q'));

        $categories = Category::query()
            ->with('parent')
            ->withCount('products')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.categories.index', compact('categories', 'keyword'));
    }

    public function create()
    {
        return view('admin.categories.create', [
            'category' => new Category,
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $data = $this->prepareData($request);

        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Đã thêm danh mục.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parents' => $this->parentOptions($category->id),
        ]);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $data = $this->prepareData($request, $category);

        if ($request->hasFile('image')) {
            if ($category->image_url) {
                Storage::disk('public')->delete($category->image_url);
            }
            $data['image_url'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return back()->with('error', 'Không thể xoá: còn danh mục con.');
        }
        if ($category->products()->exists()) {
            return back()->with('error', 'Không thể xoá: còn sản phẩm thuộc danh mục.');
        }
        if ($category->image_url) {
            Storage::disk('public')->delete($category->image_url);
        }
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Đã xoá danh mục.');
    }

    /* ---------- Helpers ---------- */
    private function prepareData(CategoryRequest $request, ?Category $category = null): array
    {
        $data = $request->validated();
        unset($data['image']);

        if (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug(Str::slug($data['name']), $category?->id);
        }
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        unset($data['image']);

        return $data;
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base;
        $i = 1;
        while (Category::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    private function parentOptions(?int $excludeId = null)
    {
        return Category::query()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }
}
