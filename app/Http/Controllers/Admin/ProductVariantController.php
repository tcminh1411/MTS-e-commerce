<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Xử lý các yêu cầu liên quan đến biến thể sản phẩm. */
class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('product_variants', 'sku')],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $data['product_id'] = $product->id;
        $data['sku'] = $data['sku'] ?: 'V-'.strtoupper(Str::random(8));
        $data['is_default'] = $request->boolean('is_default');
        $data['sort_order'] = (int) $product->variants()->max('sort_order') + 1;

        if ($data['is_default']) {
            $product->variants()->update(['is_default' => false]);
        }

        ProductVariant::create($data);

        return back()->with('success', 'Đã thêm biến thể.');
    }

    public function update(Request $request, Product $product, ProductVariant $variant)
    {
        abort_if($variant->product_id !== $product->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('product_variants', 'sku')->ignore($variant->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            $product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
        }

        $variant->update($data);

        return back()->with('success', 'Đã cập nhật biến thể.');
    }

    public function destroy(Product $product, ProductVariant $variant)
    {
        abort_if($variant->product_id !== $product->id, 404);
        $variant->delete();

        return back()->with('success', 'Đã xoá biến thể.');
    }
}
