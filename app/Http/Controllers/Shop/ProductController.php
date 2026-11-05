<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Xử lý các yêu cầu liên quan đến sản phẩm. */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::with(['images', 'brand', 'category'])->active();

        if ($kw = $request->q) {
            $q->where(fn ($x) => $x->where('name', 'like', "%{$kw}%")
                ->orWhere('short_description', 'like', "%{$kw}%"));
        }

        if ($catSlug = $request->category) {
            $cat = Category::where('slug', $catSlug)->first();
            if ($cat) {
                $ids = $cat->children()->pluck('id')->push($cat->id);
                $q->whereIn('category_id', $ids);
            }
        }

        if ($brandSlug = $request->brand) {
            $brand = Brand::where('slug', $brandSlug)->first();
            if ($brand) {
                $q->where('brand_id', $brand->id);
            }
        }

        if ($request->filled('min_price')) {
            $q->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $q->where('price', '<=', (float) $request->max_price);
        }

        match ($request->sort) {
            'price_asc' => $q->orderBy('price'),
            'price_desc' => $q->orderByDesc('price'),
            'popular' => $q->orderByDesc('sold_count'),
            default => $q->latest('published_at'),
        };

        $products = $q->paginate(12)->withQueryString();
        $categories = Category::active()->root()->with('children')->get();
        $brands = Brand::active()->orderBy('name')->get();

        return view('shop.products.index', compact('products', 'categories', 'brands'));
    }

    public function show(string $slug)
    {
        $product = Product::with([
            'images',
            'brand',
            'category',
            'variants' => fn ($query) => $query
                ->where('status', 'active')
                ->with([
                    'attributeValues',
                    'images' => fn ($images) => $images
                        ->orderByDesc('is_primary')
                        ->orderBy('sort_order'),
                ]),
            'reviews' => fn ($q) => $q->where('is_visible', true)
                ->with('user')->latest(),
        ])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $variantOptions = $product->variants->map(function ($variant) use ($product) {
            $image = $variant->images->first()?->url ?? $variant->image_url;
            if ($image && ! Str::startsWith($image, ['http://', 'https://'])) {
                $image = asset('storage/'.$image);
            }

            return [
                'id' => (int) $variant->id,
                'name' => $variant->display_name,
                'price' => (float) $variant->price,
                'salePrice' => $variant->sale_price !== null ? (float) $variant->sale_price : null,
                'priceLabel' => number_format($variant->price).'đ',
                'originalPriceLabel' => number_format($variant->price).'đ',
                'salePriceLabel' => $variant->sale_price !== null
                    ? number_format($variant->sale_price).'đ'
                    : null,
                'isOnSale' => $variant->is_on_sale,
                'stock' => (int) $variant->stock,
                'sku' => $variant->sku ?: $product->sku,
                'weightGram' => $variant->weight_gram ?? $product->weight_gram,
                'image' => $image,
                'isDefault' => $variant->is_default,
            ];
        })->values();

        $selectedVariant = $variantOptions->firstWhere('isDefault', true) ?? $variantOptions->first();
        $selectedVariantId = $selectedVariant['id'] ?? null;
        $hasVariants = $product->variants()->exists();
        $initialImage = $selectedVariant['image']
            ?? $product->images->firstWhere('is_primary', true)?->url
            ?? $product->images->first()?->url;

        $related = Product::with('images')
            ->active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return view('shop.products.show', compact(
            'product',
            'related',
            'variantOptions',
            'selectedVariantId',
            'hasVariants',
            'initialImage',
        ));
    }
}
