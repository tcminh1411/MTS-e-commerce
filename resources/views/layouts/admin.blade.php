{{-- Bố cục quản trị của ứng dụng. --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>
        @yield ('title', 'Admin')
        — MTS
    </title>
    @vite (['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="font-sans antialiased bg-gray-100 text-gray-900 min-h-screen"
    x-data="{ mobileMenu: false }"
>
    <div class="flex min-h-screen">
        {{-- Mobile backdrop --}}
        <div
            x-show="mobileMenu"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileMenu = false"
            class="fixed inset-0 bg-black/50 z-40 md:hidden"
            style="display: none"
        ></div>
        {{-- SIDEBAR --}}
        <aside
            class="w-64 bg-gray-900 text-gray-100 flex-shrink-0 flex flex-col fixed md:sticky top-0 h-screen z-50 md:z-auto transition-transform duration-300"
            :class="mobileMenu
                ? 'translate-x-0'
                : '-translate-x-full md:translate-x-0'"
        >
            <div
                class="h-16 flex items-center flex-shrink-0 px-5 border-b border-gray-800"
            >
                <a
                    href="{{ auth()->user()->hasRole('admin') ? route('admin.dashboard') : route('admin.products.index') }}"
                    class="text-lg font-bold text-indigo-400"
                >
                    MTS Admin
                </a>
            </div>

            <nav class="flex-1 p-3 space-y-1 text-sm overflow-y-auto">
                @php
                $items = [
                ['admin.dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0
                01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['admin.categories.index', 'Danh mục', 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['admin.brands.index', 'Thương hiệu','M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0
                002-2V8m-9 4h4'],
                ['admin.products.index', 'Sản phẩm', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['admin.warehouses.index', 'Kho hàng', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2
                0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                ['admin.inventories.index', 'Nhập/Xuất kho', 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                ['admin.orders.index', 'Đơn hàng', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0
                00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                ['admin.coupons.index', 'Mã giảm giá', 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002
                2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
                ['admin.orders.returns.index', 'Đổi trả / Hoàn tiền', 'M16 15v-2a4 4 0 00-4-4H5m0 0l3-3m-3 3l3 3'],
                ['admin.reviews.index', 'Đánh giá', 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0
                00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755
                1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976
                2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0
                00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                ['admin.faqs.index', 'FAQ', 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278
                2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['admin.banners.index', 'Banner', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0
                012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['admin.pages.index', 'Trang', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0
                01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['admin.post_categories.index', 'Danh mục Blog', 'M7 7h.01M7 3h5a2 2 0 011.414.586l7 7a2 2 0 010
                2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z'],
                ['admin.posts.index', 'Bài viết', 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2zM9 12h6m-6
                4h6m-6-8h2']
                ];
                @endphp

                @php
                $adminOnlyRoutes = [
                'admin.dashboard',
                'admin.categories.index', 'admin.brands.index',
                'admin.coupons.index',
                'admin.warehouses.index', 'admin.inventories.index',
                'admin.orders.returns.index', 'admin.reviews.index',
                'admin.faqs.index', 'admin.banners.index', 'admin.pages.index',
                'admin.post_categories.index', 'admin.posts.index',
                ];
                $isAdmin = auth()->user()->hasRole('admin');
                @endphp

                @foreach ($items as [$route, $label, $icon])
                    @continue (in_array($route, $adminOnlyRoutes, true) && ! $isAdmin)
                    @php $active = request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route));
                @endphp
                    <a
                        href="{{ route($route) }}"
                        @click="mobileMenu = false"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg transition
                          {{ $active ? 'bg-indigo-600 text-white' : 'hover:bg-gray-800 text-gray-300' }}"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="{{ $icon }}"
                            />
                        </svg>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="p-3 border-t border-gray-800 text-xs flex-shrink-0">
                <div class="mb-2 text-gray-400">
                    {{ auth()->user()->full_name }} — {{ auth()->user()->getRoleNames()->implode(', ') }}
                </div>
                <a
                    href="{{ route('home') }}"
                    class="block text-indigo-400 hover:underline mb-1"
                    >← Về storefront</a
                >
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-red-400 hover:underline">
                        Đăng xuất
                    </button>
                </form>
            </div>
        </aside>

        {{-- MAIN --}}
        <div class="flex-1 flex flex-col min-w-0">
            <header
                class="h-16 bg-white border-b flex items-center justify-between px-4 md:px-6"
            >
                <div class="flex items-center gap-2">
                    <button
                        @click="mobileMenu = !mobileMenu"
                        class="md:hidden p-2 -ml-2 text-gray-700 hover:bg-gray-100 rounded-lg"
                        aria-label="Menu"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>
                    <div
                        class="text-xs text-gray-400 uppercase tracking-wider hidden md:block"
                    >
                        Bảng quản trị
                    </div>
                </div>
                <div class="text-sm text-gray-500 hidden md:block">
                    {{ now()->format('d/m/Y H:i') }}
                </div>
            </header>

            <main class="flex-1 p-6 overflow-x-auto font-sans text-sm text-gray-900">
                @if (session('success'))
                    <div
                        class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm"
                    >
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div
                        class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm"
                    >
                        {{ session('error') }}
                    </div>
                @endif

                @yield ('content')
            </main>
        </div>
    </div>
</body>
</html>
