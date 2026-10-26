<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Xử lý các yêu cầu liên quan đến thương hiệu. */
class BrandController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q'));

        $brands = Brand::query()
            ->withCount('products')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.brands.index', compact('brands', 'keyword'));
    }

    public function create()
    {
        return view('admin.brands.create', ['brand' => new Brand]);
    }

    public function store(BrandRequest $request)
    {
        $data = $this->prepareData($request);

        if ($request->hasFile('logo')) {
            $data['logo_url'] = $request->file('logo')->store('brands', 'public');
        }

        Brand::create($data);

        return redirect()->route('admin.brands.index')->with('success', 'Đã thêm thương hiệu.');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand)
    {
        $data = $this->prepareData($request, $brand);

        if ($request->hasFile('logo')) {
            if ($brand->logo_url) {
                Storage::disk('public')->delete($brand->logo_url);
            }
            $data['logo_url'] = $request->file('logo')->store('brands', 'public');
        }

        $brand->update($data);

        return redirect()->route('admin.brands.index')->with('success', 'Đã cập nhật thương hiệu.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return back()->with('error', 'Không thể xoá: còn sản phẩm thuộc thương hiệu.');
        }
        if ($brand->logo_url) {
            Storage::disk('public')->delete($brand->logo_url);
        }
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Đã xoá thương hiệu.');
    }

    private function prepareData(BrandRequest $request, ?Brand $brand = null): array
    {
        $data = $request->validated();
        unset($data['logo']);

        if (empty($data['slug'])) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $i = 1;
            while (Brand::where('slug', $slug)
                ->when($brand, fn ($q) => $q->where('id', '!=', $brand->id))
                ->exists()
            ) {
                $slug = $base.'-'.(++$i);
            }
            $data['slug'] = $slug;
        }
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
