<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Xử lý các yêu cầu liên quan đến banner quảng cáo. */
class BannerController extends Controller
{
    public function index(Request $request)
    {
        $position = $request->query('position');
        $keyword = trim((string) $request->query('q'));

        $query = Banner::query()->ordered();

        if ($position && array_key_exists($position, Banner::POSITIONS)) {
            $query->where('position', $position);
        }

        $banners = $query
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('link', 'like', "%{$keyword}%");
                });
            })
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => Banner::count(),
            'active' => Banner::where('is_active', true)->count(),
            'inactive' => Banner::where('is_active', false)->count(),
        ];

        return view('admin.banners.index', compact('banners', 'counts', 'position', 'keyword'));
    }

    public function create()
    {
        $banner = new Banner([
            'position' => 'home_top',
            'is_active' => true,
            'sort_order' => (Banner::max('sort_order') ?? 0) + 1,
        ]);

        return view('admin.banners.create', compact('banner'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, isCreate: true);
        $data['image'] = $this->resolveImage($request, null);

        Banner::create($data);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Đã thêm banner mới.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $this->validateData($request, isCreate: false);
        $data['image'] = $this->resolveImage($request, $banner->image);

        $banner->update($data);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Đã cập nhật banner #'.$banner->id.'.');
    }

    public function destroy(Banner $banner)
    {
        // Xoá file vật lý nếu là local path (không phải URL external)
        if (! str_starts_with((string) $banner->image, 'http')) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return back()->with('success', 'Đã xoá banner #'.$banner->id.'.');
    }

    // ===== Helpers =====

    private function validateData(Request $request, bool $isCreate): array
    {
        // Khi tạo mới: bắt buộc phải có 1 trong 2 (file hoặc URL)
        if ($isCreate) {
            $request->validate([
                'image_file' => ['required_without:image_url'],
                'image_url' => ['required_without:image_file'],
            ], [
                'image_file.required_without' => 'Vui lòng upload ảnh hoặc nhập URL ảnh.',
                'image_url.required_without' => 'Vui lòng upload ảnh hoặc nhập URL ảnh.',
            ]);
        }

        return $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'link' => ['nullable', 'string', 'max:500'],
            'position' => ['required', 'string', 'in:'.implode(',', array_keys(Banner::POSITIONS))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'image_file.image' => 'File phải là ảnh.',
            'image_file.mimes' => 'Chỉ chấp nhận JPG, PNG, WEBP.',
            'image_file.max' => 'Ảnh tối đa 2MB.',
            'image_url.url' => 'URL ảnh không hợp lệ.',
            'position.required' => 'Vui lòng chọn vị trí.',
            'position.in' => 'Vị trí không hợp lệ.',
        ]);
    }

    /**
     * Trả về giá trị cuối cùng cho cột `image`:
     *  - Nếu upload file → lưu storage, xoá file cũ (nếu là local) → trả path
     *  - Nếu nhập URL → xoá file cũ (nếu là local) → trả URL
     *  - Nếu không upload + không URL → giữ nguyên $oldImage
     */
    private function resolveImage(Request $request, ?string $oldImage): string
    {
        // Trường hợp upload file
        if ($request->hasFile('image_file')) {
            if ($oldImage && ! str_starts_with($oldImage, 'http')) {
                Storage::disk('public')->delete($oldImage);
            }

            return $request->file('image_file')->store('banners', 'public');
        }

        // Trường hợp nhập URL
        $url = trim((string) $request->input('image_url', ''));
        if ($url !== '') {
            if ($oldImage && ! str_starts_with($oldImage, 'http')) {
                Storage::disk('public')->delete($oldImage);
            }

            return $url;
        }

        // Không thay đổi → giữ ảnh cũ
        if (! $oldImage) {
            // Trường hợp create mà không có gì → fallback placeholder (không nên xảy ra vì đã validate riêng)
            abort(422, 'Vui lòng chọn ảnh upload hoặc nhập URL.');
        }

        return $oldImage;
    }
}
