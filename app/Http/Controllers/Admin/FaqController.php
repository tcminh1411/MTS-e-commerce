<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

/** Xử lý các yêu cầu liên quan đến câu hỏi thường gặp. */
class FaqController extends Controller
{
    /**
     * Danh sách FAQ + filter + search.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $category = $request->query('category');

        $query = Faq::query()->ordered();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if ($category && array_key_exists($category, Faq::categories())) {
            $query->where('category', $category);
        }

        $faqs = $query->paginate(15)->withQueryString();

        $counts = [
            'all' => Faq::count(),
            'active' => Faq::where('is_active', true)->count(),
            'inactive' => Faq::where('is_active', false)->count(),
        ];

        return view('admin.faqs.index', compact('faqs', 'counts', 'search', 'category'));
    }

    public function create()
    {
        $faq = new Faq([
            'is_active' => true,
            'sort_order' => (Faq::max('sort_order') ?? 0) + 1,
        ]);

        return view('admin.faqs.create', compact('faq'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        Faq::create($data);

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'Đã thêm FAQ mới.');
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq)
    {
        $data = $this->validateData($request, $faq->id);

        $faq->update($data);

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'Đã cập nhật FAQ #'.$faq->id.'.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return back()->with('success', 'Đã xoá FAQ #'.$faq->id.'.');
    }

    /**
     * Validate dùng chung cho store + update.
     */
    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'in:'.implode(',', array_keys(Faq::categories()))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'question.required' => 'Vui lòng nhập câu hỏi.',
            'question.max' => 'Câu hỏi tối đa 255 ký tự.',
            'answer.required' => 'Vui lòng nhập câu trả lời.',
            'answer.max' => 'Câu trả lời tối đa 5000 ký tự.',
            'category.in' => 'Danh mục không hợp lệ.',
            'sort_order.integer' => 'Thứ tự phải là số.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
