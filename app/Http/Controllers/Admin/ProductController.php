<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductVariantService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(): View
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

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product(['status' => 'draft', 'price' => 0, 'product_type' => 'variable']),
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'attributes' => Attribute::with('values')->orderBy('sort_order')->orderBy('name')->get(),
            'assignedAttributeSettings' => [],
            'existingVariants' => [], // Mảng rỗng khi tạo mới
        ]);
    }

    public function store(
        ProductRequest $request,
        ProductVariantService $productVariantService
    ): RedirectResponse {
        $product = DB::transaction(function () use ($request, $productVariantService) {
            $data = $this->prepareData($request);
            $product = Product::create($data);
            $this->syncAttributes($product, $request);

            // Xử lý ma trận biến thể
            $this->syncVariants($product, $request);

            if ($product->product_type === 'simple') {
                $productVariantService->ensureDefaultForSimpleProduct($product);
            }

            $this->handleImages($product, $request);

            return $product;
        });

        return redirect()->route('admin.products.index')
            ->with('success', "Đã thêm sản phẩm #{$product->id}.");
    }

    public function edit(Product $product): View
    {
        $product->load([
            'images',
            'productAttributes.attribute.values',
            'variants.attributeValues', // Load sẵn thuộc tính của biến thể
        ]);

        // Chuyển dữ liệu biến thể hiện tại thành JSON để JavaScript đọc
        $existingVariants = $product->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'price' => $variant->price,
                'sale_price' => $variant->sale_price,
                'stock' => $variant->stock,
                'status' => $variant->status,
                'attribute_value_ids' => $variant->attributeValues->pluck('id')->toArray(),
            ];
        })->values()->toArray();

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'attributes' => Attribute::with('values')->orderBy('sort_order')->orderBy('name')->get(),
            'assignedAttributeSettings' => $product->productAttributes
                ->mapWithKeys(fn ($productAttribute) => [
                    $productAttribute->attribute_id => ['is_variation' => $productAttribute->is_variation],
                ])
                ->all(),
            'existingVariants' => $existingVariants, // Truyền sang View
        ]);
    }

    public function update(
        ProductRequest $request,
        Product $product,
        ProductVariantService $productVariantService
    ): RedirectResponse {
        DB::transaction(function () use ($request, $product, $productVariantService) {
            $product->update($this->prepareData($request, $product));
            $this->syncAttributes($product, $request);

            // Xử lý ma trận biến thể (Cập nhật / Thêm mới / Xóa mềm)
            $this->syncVariants($product, $request);

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

    public function destroy(Product $product): RedirectResponse
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
        unset(
            $data['images'],
            $data['remove_images'],
            $data['delete_variant_ids'],
            $data['attribute_ids'],
            $data['variation_attribute_ids'],
            $data['variants'], // Xóa mảng variants khỏi data chính
        );

        if (! isset($data['product_type'])) {
            $data['product_type'] = $product?->product_type ?? 'variable';
        }

        if (empty($data['slug'])) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $i = 0;
            $ignoreId = $product?->id;

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
        $data['created_by'] = $data['created_by'] ?? $request->user()?->id;

        if ($data['status'] === 'active' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    private function syncAttributes(Product $product, ProductRequest $request): void
    {
        $attributeIds = array_map('intval', $request->input('attribute_ids') ?? []);
        $variationAttributeIds = array_map('intval', $request->input('variation_attribute_ids') ?? []);

        if (array_diff($variationAttributeIds, $attributeIds) !== []) {
            throw ValidationException::withMessages([
                'variation_attribute_ids' => 'Chỉ có thể dùng thuộc tính đã gán cho sản phẩm để tạo biến thể.',
            ]);
        }

        $usedAttributeIds = DB::table('variant_attribute_values')
            ->join('attribute_values', 'attribute_values.id', '=', 'variant_attribute_values.attribute_value_id')
            ->join('product_variants', 'product_variants.id', '=', 'variant_attribute_values.variant_id')
            ->where('product_variants.product_id', $product->id)
            ->distinct()
            ->pluck('attribute_values.attribute_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (array_diff($usedAttributeIds, $variationAttributeIds) !== []) {
            throw ValidationException::withMessages([
                'attribute_ids' => 'Không thể bỏ thuộc tính đang được dùng bởi biến thể.',
            ]);
        }

        $product->productAttributes()->delete();

        foreach ($attributeIds as $sortOrder => $attributeId) {
            $product->productAttributes()->create([
                'attribute_id' => $attributeId,
                'is_variation' => in_array($attributeId, $variationAttributeIds, true),
                'sort_order' => $sortOrder,
            ]);
        }
    }

    /**
     * Hàm xử lý Ma trận Biến thể (Thêm/Sửa/Xóa mềm)
     */
    private function syncVariants(Product $product, ProductRequest $request): void
    {
        $submittedVariants = $request->input('variants', []);
        $submittedVariantIds = [];

        // 1. Duyệt qua các biến thể từ Form gửi lên
        foreach ($submittedVariants as $vData) {
            if (empty($vData['sku']) || empty($vData['attribute_value_ids'])) {
                continue; // Bỏ qua nếu dữ liệu không hợp lệ
            }

            // Nếu có ID -> Cập nhật biến thể cũ
            if (! empty($vData['id'])) {
                $variant = $product->variants()->withTrashed()->find($vData['id']);
                if ($variant) {
                    $variant->update([
                        'sku' => $vData['sku'],
                        'name' => $vData['name'] ?? '',
                        'price' => $vData['price'] ?? 0,
                        'sale_price' => $vData['sale_price'] ?? null,
                        'status' => $vData['status'] ?? 'active',
                        'deleted_at' => null, // Khôi phục nếu lỡ bị xóa mềm
                    ]);
                    $submittedVariantIds[] = $variant->id;
                    $variant->attributeValues()->sync($vData['attribute_value_ids']);
                }
            }
            // Nếu không có ID -> Tạo biến thể mới
            else {
                $variant = $product->variants()->create([
                    'sku' => $vData['sku'],
                    'name' => $vData['name'] ?? '',
                    'price' => $vData['price'] ?? 0,
                    'sale_price' => $vData['sale_price'] ?? null,
                    'status' => $vData['status'] ?? 'active',
                    'is_default' => false,
                ]);
                $submittedVariantIds[] = $variant->id;
                $variant->attributeValues()->sync($vData['attribute_value_ids']);
            }
        }

        // 2. Những biến thể cũ không còn trong Form -> Xóa mềm (Soft Delete)
        // Giúp bảo vệ dữ liệu của các đơn hàng cũ
        if ($product->exists) {
            $variantsToRemove = $product->variants()
                ->whereNotIn('id', $submittedVariantIds)
                ->get();

            foreach ($variantsToRemove as $oldVariant) {
                // Chỉ xóa mềm, không xóa cứng khỏi database
                $oldVariant->delete();
            }
        }
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
