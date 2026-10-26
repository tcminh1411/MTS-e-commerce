<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Xử lý các yêu cầu liên quan đến danh sách yêu thích. */
class WishlistController extends Controller
{
    public function index(): View
    {
        $items = Wishlist::query()
            ->where('user_id', Auth::id())
            ->with(['product' => function ($q) {
                $q->active()->with('images');
            }])
            ->latest()
            ->paginate(12);

        return view('shop.wishlist.index', compact('items'));
    }

    public function toggle(Product $product): RedirectResponse
    {
        $userId = Auth::id();

        $existing = Wishlist::query()
            ->where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return back()->with('success', 'Đã xoá khỏi danh sách yêu thích.');
        }

        Wishlist::create([
            'user_id' => $userId,
            'product_id' => $product->id,
        ]);

        return back()->with('success', 'Đã thêm vào danh sách yêu thích.');
    }
}
