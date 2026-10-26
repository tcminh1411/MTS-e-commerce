<?php

namespace App\Providers;

use App\Models\Page;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/** Đăng ký view composer và dịch vụ dùng chung của ứng dụng. */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.storefront', function ($view) {
            $view->with('footerPages', Page::active()->footer()
                ->orderBy('footer_sort')
                ->orderBy('id')
                ->get());
        });
    }
}
