<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/** Xử lý các yêu cầu liên quan đến biến thể sản phẩm. */
class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $imageUrl = null;
        try {
            DB::transaction(function () use ($request, $product, &$imageUrl): void {
                $this->syncProductAttributesFromRequest($product, $request);
                [$data, $attributeValueIds] = $this->validateVariantData($request, $product);

                $data['product_id'] = $product->id;
                $data['sku'] = $data['sku'] ?: 'V-'.strtoupper(Str::random(8));
                $data['is_default'] = $request->boolean('is_default');
                $data['sort_order'] = (int) $product->variants()->max('sort_order') + 1;
                $data['image_url'] = $imageUrl = $this->resolveImage($request);
                unset($data['attribute_values']);

                if ($data['is_default']) {
                    $product->variants()->update(['is_default' => false]);
                }

                $variant = ProductVariant::create($data);
                $variant->attributeValues()->sync($attributeValueIds);
                $product->update(['product_type' => 'variable']);
            });
        } catch (Throwable $exception) {
            if ($request->hasFile('image_file') && $imageUrl !== null) {
                $this->deleteLocalImage($imageUrl);
            }

            throw $exception;
        }

        return back()->with('success', 'Đã thêm biến thể.');
    }

    private function syncProductAttributesFromRequest(Product $product, Request $request): void
    {
        if (! $request->exists('attribute_ids') && ! $request->exists('variation_attribute_ids')) {
            return;
        }

        $data = $request->validate([
            'attribute_ids' => ['sometimes', 'array'],
            'attribute_ids.*' => ['integer', 'distinct', Rule::exists('attributes', 'id')],
            'variation_attribute_ids' => ['sometimes', 'array'],
            'variation_attribute_ids.*' => ['integer', 'distinct', Rule::exists('attributes', 'id')],
        ], [
            'attribute_ids.*.exists' => 'Thuộc tính đã chọn không hợp lệ.',
            'variation_attribute_ids.*.exists' => 'Thuộc tính biến thể đã chọn không hợp lệ.',
        ]);

        $attributeIds = array_map('intval', $data['attribute_ids'] ?? []);
        $variationAttributeIds = array_map('intval', $data['variation_attribute_ids'] ?? []);

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

    public function update(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_if($variant->product_id !== $product->id, 404);

        [$data, $attributeValueIds] = $this->validateVariantData($request, $product, $variant);

        $data['sku'] = $data['sku'] ?: $variant->sku;
        $data['is_default'] = $request->boolean('is_default');
        $oldImage = $variant->getRawOriginal('image_url');
        $data['image_url'] = $this->resolveImage($request, $oldImage);
        unset($data['attribute_values']);

        try {
            DB::transaction(function () use ($data, $product, $variant, $attributeValueIds): void {
                if ($data['is_default']) {
                    $product->variants()
                        ->where('id', '!=', $variant->id)
                        ->update(['is_default' => false]);
                }

                $variant->update($data);
                $variant->attributeValues()->sync($attributeValueIds);
            });
        } catch (Throwable $exception) {
            if ($request->hasFile('image_file')) {
                $this->deleteLocalImage($data['image_url']);
            }

            throw $exception;
        }

        if ($data['image_url'] !== $oldImage) {
            $this->deleteLocalImage($oldImage);
        }

        return back()->with('success', 'Đã cập nhật biến thể.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, int>}
     */
    private function validateVariantData(
        Request $request,
        Product $product,
        ?ProductVariant $variant = null
    ): array {
        $skuRule = Rule::unique('product_variants', 'sku');

        if ($variant !== null) {
            $skuRule->ignore($variant->id);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:200'],
            'auto_name' => ['nullable', 'boolean'],
            'sku' => ['nullable', 'string', 'max:64', $skuRule],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'image_url' => ['nullable', 'url:http,https', 'max:500'],
            'attribute_values' => ['nullable', 'array'],
            'attribute_values.*' => [
                'integer',
                'distinct',
                Rule::exists('attribute_values', 'id'),
            ],
        ], [
            'name.required' => 'Vui lòng nhập tên biến thể hoặc chọn giá trị thuộc tính.',
            'image_file.image' => 'File biến thể phải là ảnh.',
            'image_file.mimes' => 'Ảnh biến thể chỉ nhận JPG, PNG hoặc WEBP.',
            'image_file.max' => 'Ảnh biến thể tối đa 2MB.',
            'image_url.url' => 'URL ảnh phải bắt đầu bằng http hoặc https.',
        ]);

        $attributeValueIds = array_map('intval', $data['attribute_values'] ?? []);
        $selectedValues = AttributeValue::query()
            ->whereIn('id', $attributeValueIds)
            ->get();
        $variationAttributes = $product->productAttributes()
            ->where('is_variation', true)
            ->with('attribute')
            ->orderBy('sort_order')
            ->get()
            ->pluck('attribute')
            ->filter()
            ->values();
        $variationAttributeIds = $variationAttributes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedAttributeIds = $selectedValues->pluck('attribute_id')->map(fn ($id) => (int) $id)->all();

        if (array_diff($selectedAttributeIds, $variationAttributeIds) !== []) {
            throw ValidationException::withMessages([
                'attribute_values' => 'Giá trị phải thuộc thuộc tính biến thể đã gán cho sản phẩm.',
            ]);
        }

        if (count($selectedAttributeIds) !== count($variationAttributeIds)
            || array_diff($variationAttributeIds, $selectedAttributeIds) !== []
        ) {
            if ($variationAttributeIds !== []) {
                throw ValidationException::withMessages([
                    'attribute_values' => 'Hãy chọn đúng một giá trị cho mỗi thuộc tính biến thể.',
                ]);
            }
        }

        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '' || $request->boolean('auto_name')) {
            if ($selectedValues->isEmpty()) {
                throw ValidationException::withMessages([
                    'name' => 'Vui lòng nhập tên biến thể hoặc chọn giá trị thuộc tính.',
                ]);
            }

            $selectedValuesByAttribute = $selectedValues->keyBy('attribute_id');
            $name = $variationAttributes
                ->map(fn ($attribute) => $selectedValuesByAttribute->get($attribute->id)?->value)
                ->filter()
                ->implode(' / ');
        }

        $data['name'] = $name;
        unset($data['auto_name'], $data['image_file']);

        return [$data, $attributeValueIds];
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_if($variant->product_id !== $product->id, 404);

        $variant->delete();

        return back()->with('success', 'Đã xoá biến thể.');
    }

    private function resolveImage(Request $request, ?string $currentImage = null): ?string
    {
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('variants', 'public');

            if ($imagePath === false) {
                throw new RuntimeException('Không thể lưu ảnh biến thể.');
            }

            return $imagePath;
        }

        if ($request->filled('image_url')) {
            return trim($request->string('image_url')->toString());
        }

        return $currentImage;
    }

    private function deleteLocalImage(?string $image): void
    {
        if ($image && ! Str::startsWith($image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($image);
        }
    }
}
