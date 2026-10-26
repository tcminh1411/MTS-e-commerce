{{-- Bố cục cửa hàng của ứng dụng. --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield ('title', config('app.name', 'MTS'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link
        href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700|manrope:400,500,600,700|jetbrains-mono:400,500,600&display=swap"
        rel="stylesheet"
    />
    @vite (['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>

<body class="font-sans antialiased bg-bg text-ink min-h-screen flex flex-col">
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-4">
            <a
                href="{{ route('home') }}"
                class="text-2xl font-bold text-ink font-serif tracking-tight flex-shrink-0"
            >
                MTS
            </a>

            <form
                action="{{ route('products.index') }}"
                method="GET"
                class="flex-1 max-w-lg hidden md:block"
            >
                <div class="relative">
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Tìm sản phẩm..."
                        class="w-full border rounded-lg pl-3 pr-10 py-2 text-sm"
                    />
                    <button
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </button>
                </div>
            </form>

            <nav class="flex items-center gap-4 text-sm font-medium ml-auto">
                <a
                    href="{{ route('home') }}"
                    class="hidden md:inline hover:text-indigo-600"
                    >Trang chủ</a
                >
                <a
                    href="{{ route('products.index') }}"
                    class="hidden md:inline hover:text-indigo-600"
                    >Sản phẩm</a
                >

                <a
                    href="{{ route('wishlist.index') }}"
                    class="relative hover:text-indigo-600"
                >
                    Yêu thích
                    @php
                    $wishlistCount = \App\Models\Wishlist::where('user_id',
                    \Illuminate\Support\Facades\Auth::id())->count();
                    @endphp
                    @if ($wishlistCount)
                        <span
                            class="absolute -top-2 -right-3 bg-red-500 text-white text-[10px] px-1.5 rounded-full"
                            >{{ $wishlistCount }}</span
                        >
                    @endif
                </a>

                <a
                    href="{{ route('cart.index') }}"
                    class="relative hover:text-indigo-600"
                >
                    Giỏ hàng
                    @php
                    $cartCount = \Illuminate\Support\Facades\Auth::check()
                    ? \App\Models\Cart::where('user_id', \Illuminate\Support\Facades\Auth::id())
                    ->withSum('items', 'quantity')->first()?->items_sum_quantity
                    : (session('guest_cart_token')
                    ? \App\Models\Cart::where('session_token', session('guest_cart_token'))
                    ->withSum('items', 'quantity')->first()?->items_sum_quantity
                    : 0);
                    @endphp
                    @if ($cartCount)
                        <span
                            class="absolute -top-2 -right-3 bg-red-500 text-white text-[10px] px-1.5 rounded-full"
                            >{{ $cartCount }}</span
                        >
                    @endif
                </a>

                @auth
                    <a
                        href="{{ route('orders.index') }}"
                        class="hover:text-indigo-600"
                        >Đơn hàng</a
                    >
                @endauth

                <a
                    href="{{ route('faq.index') }}"
                    class="hidden md:inline hover:text-indigo-600"
                    >FAQ</a
                >
                <a
                    href="{{ route('blog.index') }}"
                    class="hover:text-indigo-600"
                    >Blog</a
                >

                @auth
                    @if (\Illuminate\Support\Facades\Auth::user()->hasAnyRole(['admin', 'staff']))
                        <a
                            href="{{ route('dashboard') }}"
                            class="px-3 py-1 rounded bg-indigo-50 text-indigo-700 text-xs"
                            >Quản trị</a
                        >
                    @endif

                    <a
                        href="{{ route('profile.edit') }}"
                        class="hidden md:flex items-center gap-2 hover:text-indigo-600"
                    >
                        @if (\Illuminate\Support\Facades\Auth::user()->avatar_url_full)
                            <img
                                src="{{ \Illuminate\Support\Facades\Auth::user()->avatar_url_full }}"
                                alt="avatar"
                                class="w-8 h-8 rounded-full object-cover border border-gray-200"
                            />
                        @else
                            <span
                                class="w-8 h-8 rounded-full bg-sage text-white flex items-center justify-center text-xs font-bold"
                            >
                                {{ strtoupper(mb_substr(\Illuminate\Support\Facades\Auth::user()->full_name ?? 'U', 0, 1)) }}
                            </span>
                        @endif
                        <span
                            class="hidden md:inline"
                            >{{ \Illuminate\Support\Facades\Auth::user()->full_name ?? \Illuminate\Support\Facades\Auth::user()->email }}</span
                        >
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="text-red-600 hover:underline text-sm"
                        >
                            Đăng xuất
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-indigo-600"
                        >Đăng nhập</a
                    >
                    <a
                        href="{{ route('register') }}"
                        class="px-3 py-1.5 rounded bg-sage text-white text-xs hover:opacity-90 transition"
                    >
                        Đăng ký
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1 w-full max-w-7xl mx-auto px-4 py-6">
        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @yield ('content')
    </main>

    <footer class="bg-white border-t mt-12">
        @if ($footerPages->isNotEmpty())
            <div
                class="max-w-7xl mx-auto px-4 pt-6 pb-3 text-sm text-gray-500"
                style="
                    display: flex;
                    justify-content: center;
                    gap: 24px;
                    flex-wrap: wrap;
                "
            >
                @foreach ($footerPages as $fp)
                    <a
                        href="{{ route('page.show', $fp) }}"
                        class="hover:text-indigo-600"
                        >{{ $fp->title }}</a
                    >
                @endforeach
            </div>
        @endif
        <div
            class="max-w-7xl mx-auto px-4 py-6 text-sm text-gray-500 flex justify-between"
        >
            <span>© {{ date('Y') }} MTS — Đồ án tốt nghiệp</span>
            <span>Laravel {{ app()->version() }}</span>
        </div>
    </footer>
    @include ('partials.chatbot')
</body>
</html>
