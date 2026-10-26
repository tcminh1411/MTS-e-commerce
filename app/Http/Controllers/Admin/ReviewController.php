<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Xử lý các yêu cầu liên quan đến đánh giá sản phẩm. */
class ReviewController extends Controller
{
    /**
     * Danh sách đánh giá và lọc theo trạng thái hiển thị.
     */
    public function index(Request $request)
    {
        $visibility = $request->query('visibility');
        $search = trim((string) $request->query('q'));

        $query = Review::with(['user', 'product', 'order'])
            ->latest();

        if (in_array($visibility, ['visible', 'hidden'], true)) {
            $query->where('is_visible', $visibility === 'visible');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $reviews = $query->paginate(15)->withQueryString();

        // Đếm đánh giá đang hiển thị và đã ẩn cho bộ lọc.
        $counts = [
            'all' => Review::count(),
            'visible' => Review::where('is_visible', true)->count(),
            'hidden' => Review::where('is_visible', false)->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'counts', 'visibility', 'search'));
    }

    public function hide(Review $review): RedirectResponse
    {
        if (! $review->is_visible) {
            return back()->with('info', 'Đánh giá này đã được ẩn.');
        }

        $review->update(['is_visible' => false]);

        $review->product?->recalculateRating();

        return back()->with('success', 'Đã ẩn đánh giá #'.$review->id.'.');
    }

    public function show(Review $review): RedirectResponse
    {
        if ($review->is_visible) {
            return back()->with('info', 'Đánh giá này đang hiển thị.');
        }

        $review->update(['is_visible' => true]);

        $review->product?->recalculateRating();

        return back()->with('success', 'Đã hiển thị đánh giá #'.$review->id.'.');
    }
}
