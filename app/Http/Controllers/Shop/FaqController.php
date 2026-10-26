<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Faq;

/** Xử lý các yêu cầu liên quan đến câu hỏi thường gặp. */
class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::active()
            ->ordered()
            ->get()
            ->groupBy('category');

        // Sắp xếp category theo thứ tự cố định trong Faq::categories()
        $order = array_keys(Faq::categories());
        $faqs = $faqs->sortBy(function ($items, $key) use ($order) {
            $idx = array_search($key, $order, true);

            return $idx === false ? 999 : $idx;
        });

        return view('shop.faqs.index', compact('faqs'));
    }
}
