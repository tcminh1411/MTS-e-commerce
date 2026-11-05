<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $activeVariants = $product->variants()->where('status', 'active');
        $variant = null;

        if ($product->variants()->exists()) {
            if (empty($data['variant_id'])) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Vui lòng chọn phân loại sản phẩm.',
                ]);
            }
        }

        if (! empty($data['variant_id'])) {
            $variant = $activeVariants->whereKey($data['variant_id'])->first();

            if (! $variant) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Phân loại không hợp lệ cho sản phẩm này.',
                ]);
            }
        }

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
