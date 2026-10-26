<?php

// Định nghĩa các route cho cửa hàng và khu vực quản trị.

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ReturnRequestController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shop\BlogController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\ChatbotController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\InvoiceController;
use App\Http\Controllers\Shop\ProductController as ShopProductController;
use App\Http\Controllers\Shop\StockAlertController;
use App\Http\Controllers\Shop\VNPayController;
use App\Http\Controllers\Shop\WishlistController;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
1. STOREFRONT
Trang chủ, danh sách sản phẩm và trang chi tiết sản phẩm.
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ShopProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ShopProductController::class, 'show'])->name('products.show');

/*
2. DASHBOARD
Điều hướng theo vai trò: admin/staff vào khu quản trị, người dùng thường về trang chủ.
*/

Route::get('/dashboard', function () {
    /** @var User|null $user */
    $user = Auth::user();

    if ($user && $user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user && $user->hasRole('staff')) {
        return redirect()->route('admin.products.index');
    }

    return redirect()->route('home');
})->middleware(['auth'])->name('dashboard');

/*
3. TÀI KHOẢN NGƯỜI DÙNG
Hồ sơ cá nhân và danh sách sản phẩm yêu thích, bắt buộc đăng nhập.
*/

Route::middleware('auth')->group(function () {
    // Hồ sơ cá nhân
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Danh sách yêu thích
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
});

/*
4. GIỎ HÀNG
Công khai, giới hạn 60 request/phút.
*/

Route::prefix('cart')->name('cart.')->middleware('throttle:60,1')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/', [CartController::class, 'store'])->name('store');
    Route::patch('/{item}', [CartController::class, 'update'])->name('update');
    Route::delete('/{item}', [CartController::class, 'destroy'])->name('destroy');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
});

/*
5. THÔNG BÁO TỒN KHO
Công khai, giới hạn 5 request/phút.
Khách để lại email để nhận thông báo khi sản phẩm có hàng trở lại.
*/

Route::post('/stock-alerts/{product}', [StockAlertController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('stock-alerts.store');

/*
6. THANH TOÁN
Công khai, đặt hàng giới hạn 10 request/phút.
*/

Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'place'])->middleware('throttle:10,1')->name('place');
    Route::get('/success/{order}', [CheckoutController::class, 'success'])->name('success');
});

/*
7. VNPAY
return: trình duyệt người dùng quay về.
ipn: VNPay gọi server-to-server để xác nhận giao dịch.
*/

Route::get('/vnpay/return', [VNPayController::class, 'return'])->name('vnpay.return');
Route::get('/vnpay/ipn', [VNPayController::class, 'ipn'])->name('vnpay.ipn');

/*
8. ĐƠN HÀNG CỦA KHÁCH
Tra cứu đơn hàng, tải hóa đơn, yêu cầu trả hàng và đánh giá sản phẩm.
*/

Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [App\Http\Controllers\Shop\OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [App\Http\Controllers\Shop\OrderController::class, 'show'])->name('show');
    Route::post('/{order}/cancel', [App\Http\Controllers\Shop\OrderController::class, 'cancel'])->name('cancel');
    Route::get('/{order}/invoice', [InvoiceController::class, 'download'])->name('invoice');

    // Yêu cầu trả hàng
    Route::get('/{order}/return', [App\Http\Controllers\Shop\ReturnRequestController::class, 'create'])->name('return.create');
    Route::post('/{order}/return', [App\Http\Controllers\Shop\ReturnRequestController::class, 'store'])->name('return.store');

    // Đánh giá sản phẩm, bắt buộc đăng nhập và chỉ áp dụng cho đơn đã hoàn thành
    Route::middleware('auth')->group(function () {
        Route::get('/{order}/reviews/create', [App\Http\Controllers\Shop\ReviewController::class, 'create'])->name('reviews.create');
        Route::post('/{order}/reviews', [App\Http\Controllers\Shop\ReviewController::class, 'store'])->name('reviews.store');
    });
});

/*
9. TRANG NỘI DUNG CÔNG KHAI
FAQ, trang tĩnh và blog.
*/

// Câu hỏi thường gặp
Route::get('/faq', [App\Http\Controllers\Shop\FaqController::class, 'index'])->name('faq.index');

// Trang tĩnh: About / Terms / Privacy
Route::get('/page/{page}', [App\Http\Controllers\Shop\PageController::class, 'show'])->name('page.show');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post}', [BlogController::class, 'show'])->name('blog.show');

// Chatbot AI
Route::post('/chatbot', [ChatbotController::class, 'reply'])->name('chatbot.reply');
/*
10. KHU VỰC QUẢN TRỊ
Yêu cầu đăng nhập và vai trò admin.
Thứ tự nhóm: Dashboard → Danh mục → Đơn hàng → Nội dung → Kho hàng.
*/

Route::middleware(['auth', 'role:admin|staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        10.1. DASHBOARD
        Trang tổng quan quản trị.
        */

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('role:admin')
            ->name('dashboard');
        // Route cho AI viết mô tả
        Route::post('products/generate-description', function (Request $request, AIService $ai) {
            $request->validate([
                'name' => ['required', 'string'],
                'category' => ['nullable', 'string'],
            ]);

            try {
                $description = $ai->generateProductDescription(
                    $request->name,
                    $request->category ?? 'Sản phẩm công nghệ'
                );

                return response()->json(['ok' => true, 'description' => $description]);
            } catch (Throwable $e) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
            }
        })->name('products.generate-description');

        /*
        10.2. DANH MỤC SẢN PHẨM
        Categories, brands, products và biến thể sản phẩm.
        */

        // Sản phẩm: shared (staff có product.create/update)
        Route::resource('products', AdminProductController::class)->except(['show', 'destroy']);

        // Biến thể sản phẩm: store/update shared, destroy admin-only
        Route::prefix('products/{product}')->name('products.')->group(function () {
            Route::post('variants', [ProductVariantController::class, 'store'])->name('variants.store');
            Route::put('variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
            Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])
                ->middleware('role:admin')
                ->name('variants.destroy');
        });

        // Danh mục + thương hiệu + xoá sản phẩm: admin-only
        Route::middleware('role:admin')->group(function () {
            Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

            Route::resource('categories', CategoryController::class)->except('show');
            Route::resource('brands', BrandController::class)->except('show');
        });

        /*
        10.3. ĐƠN HÀNG
        Danh sách, chi tiết, hóa đơn, cập nhật trạng thái và yêu cầu trả hàng.
        */

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');

            // Yêu cầu trả hàng — ADMIN ONLY, khai báo trước /{order} để tránh bị bắt nhầm
            Route::middleware('role:admin')->prefix('returns')->name('returns.')->group(function () {
                Route::get('/', [ReturnRequestController::class, 'index'])->name('index');
                Route::get('/{returnRequest}', [ReturnRequestController::class, 'show'])->name('show');
                Route::patch('/{returnRequest}/approve', [ReturnRequestController::class, 'approve'])->name('approve');
                Route::patch('/{returnRequest}/reject', [ReturnRequestController::class, 'reject'])->name('reject');
                Route::patch('/{returnRequest}/mark-refunded', [ReturnRequestController::class, 'markRefunded'])->name('markRefunded');
            });

            Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            Route::get('/{order}/invoice', [InvoiceController::class, 'download'])->name('invoice');
            Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->name('updateStatus');
            Route::patch('/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])
                ->middleware('role:admin')
                ->name('updatePaymentStatus');
        });

        /*
        10.4. NỘI DUNG
        Banner, FAQ, trang tĩnh, blog và ẩn/hiện đánh giá.
        */

        // ===== ADMIN-ONLY: Banner, FAQ, Pages, Blog, Reviews, Kho =====
        Route::middleware('role:admin')->group(function () {

            // Banner quảng cáo
            Route::resource('banners', BannerController::class)->except('show');

            // Câu hỏi thường gặp
            Route::resource('faqs', FaqController::class)->except('show');

            // Trang tĩnh: About / Terms / Privacy
            Route::resource('pages', PageController::class)->except('show');

            // Danh mục blog
            Route::resource('post_categories', PostCategoryController::class)->except('show');

            // Bài viết blog
            Route::resource('posts', PostController::class)->except('show');

            // Ẩn hoặc hiển thị đánh giá sản phẩm
            Route::prefix('reviews')->name('reviews.')->group(function () {
                Route::get('/', [ReviewController::class, 'index'])->name('index');
                Route::patch('/{review}/hide', [ReviewController::class, 'hide'])->name('hide');
                Route::patch('/{review}/show', [ReviewController::class, 'show'])->name('show');
            });

            /*
            10.5. KHO HÀNG
            Quản lý kho và nhập/xuất kho.
            */

            Route::resource('warehouses', WarehouseController::class)->except('show');
            // Mã giảm giá
            Route::resource('coupons', CouponController::class)->except('show');
            Route::get('coupons/{coupon}/usages', [CouponController::class, 'usages'])
                ->name('coupons.usages');
            // Nhập/xuất kho
            Route::prefix('inventories')->name('inventories.')->group(function () {
                Route::get('/', [InventoryController::class, 'index'])->name('index');
                Route::get('/create', [InventoryController::class, 'create'])->name('create');
                Route::post('/', [InventoryController::class, 'store'])->name('store');
            });

        }); // end admin-only group
    });

require __DIR__.'/auth.php';
