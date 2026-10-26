<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Xử lý các yêu cầu liên quan đến cảnh báo tồn kho. */
class StockAlertController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $product->id),
            ],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'variant_id.exists' => 'Biến thể không thuộc sản phẩm này.',
        ]);

        $variantId = $data['variant_id'] ?? null;

        $exists = StockAlert::query()
            ->where('product_id', $product->id)
            ->where('email', $data['email'])
            ->when(
                $variantId === null,
                fn ($q) => $q->whereNull('variant_id'),
                fn ($q) => $q->where('variant_id', $variantId)
            )
            ->exists();

        if ($exists) {
            return back()->with('success', 'Bạn đã đăng ký nhận thông báo cho sản phẩm này rồi.');
        }

        StockAlert::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'email' => $data['email'],
        ]);

        return back()->with('success', 'Đã đăng ký. Chúng tôi sẽ email khi có hàng.');
    }
}
