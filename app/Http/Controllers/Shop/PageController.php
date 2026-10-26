<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Page;

/** Xử lý các yêu cầu liên quan đến trang tĩnh. */
class PageController extends Controller
{
    public function show(Page $page)
    {
        abort_unless($page->is_active, 404);

        return view('shop.pages.show', compact('page'));
    }
}
