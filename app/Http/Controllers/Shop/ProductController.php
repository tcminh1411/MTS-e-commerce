<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

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
            'images', 'brand', 'category', 'variants',
            'reviews' => fn ($q) => $q->where('is_visible', true)
                ->with('user')->latest(),
        ])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Product::with('images')
            ->active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return view('shop.products.show', compact('product', 'related'));
    }
}
