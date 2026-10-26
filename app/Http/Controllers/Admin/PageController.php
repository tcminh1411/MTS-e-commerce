<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Xử lý các yêu cầu liên quan đến trang tĩnh. */
class PageController extends Controller
{
    public function index(Request $request)
    {
        $query = Page::query();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $pages = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create', ['page' => new Page]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if (empty($data['slug'])) {
            $data['slug'] = Page::slugify($data['title']);
        } else {
            $data['slug'] = Page::slugify($data['slug']);
        }

        Page::create($data);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Đã tạo trang "'.$data['title'].'".');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $data = $this->validateData($request, $page->id);

        if (empty($data['slug'])) {
            $data['slug'] = Page::slugify($data['title'], $page->id);
        } else {
            $data['slug'] = Page::slugify($data['slug'], $page->id);
        }

        $page->update($data);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Đã cập nhật trang "'.$page->title.'".');
    }

    public function destroy(Page $page)
    {
        $title = $page->title;
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Đã xoá trang "'.$title.'".');
    }

    /**
     * Validate dùng chung cho store + update.
     * $ignoreId: bỏ qua khi unique check slug (tránh tự so chính mình khi edit).
     */
    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('pages', 'slug')->ignore($ignoreId),
            ],
            'body' => ['required', 'string', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'show_in_footer' => ['nullable', 'boolean'],
            'footer_sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'slug.regex' => 'Slug chỉ được chứa chữ thường, số và dấu gạch ngang.',
            'slug.unique' => 'Slug này đã được dùng cho trang khác.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['show_in_footer'] = $request->boolean('show_in_footer');
        $data['footer_sort'] = (int) $request->input('footer_sort', 0);

        return $data;
    }
}
