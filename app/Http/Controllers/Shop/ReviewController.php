<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Xử lý các yêu cầu liên quan đến đánh giá sản phẩm. */
class ReviewController extends Controller
{
    /**
     * Form viết đánh giá cho 1 sản phẩm trong 1 đơn.
     * URL: /orders/{order}/reviews/create?product_id=X
     */
    public function create(Request $request, Order $order)
    {
        $this->authorizeReview($order);

        $productId = (int) $request->query('product_id');

        // 1. Sản phẩm phải có trong đơn này
        $item = $order->items()->where('product_id', $productId)->first();
        if (! $item) {
            abort(404, 'Sản phẩm không có trong đơn này.');
        }

        // 2. Chưa từng review cho (user, product, order) này
        $existing = Review::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->where('order_id', $order->id)
            ->first();
        if ($existing) {
            return redirect()
                ->route('orders.show', $order)
                ->with('info', 'Bạn đã đánh giá sản phẩm này trong đơn rồi.');
        }

        $product = Product::findOrFail($productId);

        return view('shop.reviews.create', compact('order', 'item', 'product'));
    }

    /**
     * Lưu đánh giá và hiển thị ngay trên sản phẩm.
     */
    public function store(Request $request, Order $order)
    {
        $this->authorizeReview($order);

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'product_id.required' => 'Thiếu sản phẩm.',
            'product_id.exists' => 'Sản phẩm không tồn tại.',
            'rating.required' => 'Vui lòng chọn số sao.',
            'rating.between' => 'Số sao phải từ 1 đến 5.',
            'comment.max' => 'Nội dung đánh giá tối đa 1000 ký tự.',
        ]);

        // Defensive: check lại đã mua + chưa review (đề phòng user submit 2 lần)
        $item = $order->items()->where('product_id', $data['product_id'])->first();
        if (! $item) {
            abort(404, 'Sản phẩm không có trong đơn này.');
        }

        $exists = Review::where('user_id', Auth::id())
            ->where('product_id', $data['product_id'])
            ->where('order_id', $order->id)
            ->exists();
        if ($exists) {
            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'Bạn đã đánh giá sản phẩm này trong đơn rồi.');
        }

        $review = Review::create([
            'user_id' => Auth::id(),
            'product_id' => $data['product_id'],
            'order_id' => $order->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_visible' => true,
        ]);
        $review->product?->recalculateRating();

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Cảm ơn bạn! Đánh giá đã được đăng.');
    }

    /**
     * Guard: chỉ user sở hữu đơn + đơn phải completed.
     */
    private function authorizeReview(Order $order): void
    {
        if (! Auth::check() || (int) $order->user_id !== (int) Auth::id()) {
            abort(403, 'Bạn không có quyền đánh giá đơn này.');
        }
        if ($order->status !== 'completed') {
            abort(403, 'Chỉ có thể đánh giá đơn đã hoàn thành.');
        }
    }
}
