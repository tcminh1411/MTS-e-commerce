<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductVariantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Xử lý các yêu cầu liên quan đến sản phẩm. */
class ProductController extends Controller
{
    public function index()
    {
        $q = Product::with(['category', 'brand'])
            ->withCount('images');

        if ($kw = request('q')) {
            $q->where(fn ($x) => $x->where('name', 'like', "%{$kw}%")
                ->orWhere('sku', 'like', "%{$kw}%"));
        }
        if ($cat = request('category_id')) {
            $q->where('category_id', $cat);
        }
        if ($st = request('status')) {
            $q->where('status', $st);
        }

        $products = $q->latest('id')->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('admin.products.create', [
            'product' => new Product(['status' => 'draft', 'price' => 0]),
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request, ProductVariantService $productVariantService)
    {
        $product = DB::transaction(function () use ($request, $productVariantService) {
            $data = $this->prepareData($request);
            $product = Product::create($data);

            if ($product->product_type === 'simple') {
                $productVariantService->ensureDefaultForSimpleProduct($product);
            }

            $this->handleImages($product, $request);

            return $product;
        });

        return redirect()->route('admin.products.index')
            ->with('success', "Đã thêm sản phẩm #{$product->id}.");
    }

    public function edit(Product $product)
    {
        $product->load('images');

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function update(
        ProductRequest $request,
        Product $product,
        ProductVariantService $productVariantService
    ) {
        DB::transaction(function () use ($request, $product, $productVariantService) {
            $product->update($this->prepareData($request, $product));

            if ($variantIds = $request->input('delete_variant_ids', [])) {
                $product->variants()
                    ->whereIn('id', $variantIds)
                    ->get()
                    ->each
                    ->delete();
            }

            if ($product->product_type === 'simple') {
                $productVariantService->ensureDefaultForSimpleProduct($product);
            }

            if ($ids = $request->input('remove_images', [])) {
                $toDelete = ProductImage::where('product_id', $product->id)->whereIn('id', $ids)->get();
                foreach ($toDelete as $img) {
                    $this->deleteLocalImage($img);
                    $img->delete();
                }
            }

            $this->handleImages($product, $request);
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Product $product)
    {
        DB::transaction(function () use ($product) {
            foreach ($product->images as $img) {
                $this->deleteLocalImage($img);
            }
            $product->delete();
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã xoá sản phẩm.');
    }

    /* ---------- Helpers ---------- */
    private function prepareData(ProductRequest $request, ?Product $product = null): array
    {
        $data = $request->validated();
        unset($data['images'], $data['remove_images'], $data['delete_variant_ids']);

        if (! isset($data['product_type'])) {
            $data['product_type'] = $product?->product_type ?? 'simple';
        }

        if (empty($data['slug'])) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $i = 0;
            $ignoreId = $product?->id;   // ← gán ra biến int|null

            while (Product::withTrashed()->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
            ) {
                $slug = $base.'-'.(++$i);
            }
            $data['slug'] = $slug;
        }

        if (empty($data['sku'])) {
            $data['sku'] = 'P-'.strtoupper(Str::random(8));
        }

        $data['is_featured'] = $request->boolean('is_featured');
        $data['created_by'] = $data['created_by'] ?? auth()->id();

        if ($data['status'] === 'active' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    private function handleImages(Product $product, ProductRequest $request): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        $startSort = (int) $product->images()->max('sort_order');

        foreach ($request->file('images') as $i => $file) {
            $path = $file->store("products/{$product->id}", 'public');

            $product->images()->create([
                'url' => $path,
                'alt' => $product->name,
                'sort_order' => $startSort + $i + 1,
                'is_primary' => ! $hasPrimary && $i === 0,
            ]);
        }
    }

    private function deleteLocalImage(ProductImage $image): void
    {
        $path = $image->getRawOriginal('url');

        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
