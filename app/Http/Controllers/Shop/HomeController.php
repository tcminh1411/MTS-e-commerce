<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;

/** Xử lý các yêu cầu liên quan đến trang chủ. */
class HomeController extends Controller
{
    public function index()
    {
        $featured = Product::with([
            'images',
            'brand',
            'variants' => fn ($query) => $query
                ->where('status', 'active')
                ->with('attributeValues.attribute'),
        ])
            ->withCount([
                'variants',
                'variants as active_variants_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->active()
            ->featured()
            ->latest('published_at')
            ->limit(8)
            ->get();

        $newest = Product::with([
            'images',
            'variants' => fn ($query) => $query
                ->where('status', 'active')
                ->with('attributeValues.attribute'),
        ])
            ->withCount([
                'variants',
                'variants as active_variants_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->active()
            ->latest('published_at')
            ->limit(8)
            ->get();

        $categories = Category::active()
            ->root()
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        $bannersTop = Banner::active()
            ->position('home_top')
            ->ordered()
            ->get();

        $bannersMiddle = Banner::active()
            ->position('home_middle')
            ->ordered()
            ->get();

        $bannersBottom = Banner::active()
            ->position('home_bottom')
            ->ordered()
            ->get();

        return view('shop.home', compact('featured', 'newest', 'categories', 'bannersTop', 'bannersMiddle', 'bannersBottom'));
    }
}
