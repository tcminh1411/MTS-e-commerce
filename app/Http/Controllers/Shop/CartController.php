<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\Request;

/** Xử lý các yêu cầu liên quan đến giỏ hàng. */
class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        $cart = $this->cart->current();
        $this->cart->refreshPrices($cart);
        $cart->load('items.product.images', 'items.variant');

        return view('shop.cart.index', compact('cart'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $variant = isset($data['variant_id'])
            ? ProductVariant::findOrFail($data['variant_id'])
            : null;

        $this->cart->add($product, $variant, $data['quantity']);

        return $request->wantsJson()
            ? response()->json(['ok' => true, 'count' => $this->cart->current()->items()->sum('quantity')])
            : back()->with('success', 'Đã thêm vào giỏ hàng.');
    }

    public function update(Request $request, CartItem $item)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);
        $this->cart->updateQty($item, $data['quantity']);

        return back()->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function destroy(CartItem $item)
    {
        $this->cart->remove($item);

        return back()->with('success', 'Đã xoá sản phẩm khỏi giỏ.');
    }

    public function clear()
    {
        $this->cart->clear();

        return back()->with('success', 'Đã xoá toàn bộ giỏ hàng.');
    }
}
