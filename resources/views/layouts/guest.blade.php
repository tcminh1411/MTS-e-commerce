{{-- Bố cục khách của ứng dụng. --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @php
        $routeName = request()->route()?->getName();
        $pageTitle = match($routeName) {
            'login' => 'Đăng nhập',
            'register' => 'Đăng ký',
            'password.request', 'password.email' => 'Quên mật khẩu',
            'password.reset', 'password.store' => 'Đặt lại mật khẩu',
            'password.confirm' => 'Xác nhận mật khẩu',
            'verification.notice' => 'Xác minh email',
            default => 'Tài khoản',
        };
    @endphp
    <title>{{ $pageTitle }} — MTS</title>

    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link
        href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700|manrope:400,500,600,700|jetbrains-mono:400&display=swap"
        rel="stylesheet"
    />

    @vite (['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-bg text-ink min-h-screen flex flex-col">
    <div class="flex-1 flex flex-col justify-center items-center px-4 py-12">
        {{-- Logo --}}
        <a href="{{ route('home') }}" class="mb-8 text-center group">
            <div
                class="text-5xl font-serif font-bold tracking-tight group-hover:text-sage transition"
            >
                MTS
            </div>
            <p class="text-xs text-muted mt-1 tracking-widest uppercase">Thương mại điện tử B2C</p>
        </a>

        {{-- Card chứa form --}}
        <div
            class="w-full max-w-md bg-surface rounded-2xl shadow-xl border border-gray-100 p-8"
        >
            {{ $slot }}
        </div>

        {{-- Nav phụ --}}
        <div
            class="mt-8 text-center text-xs text-muted flex items-center justify-center gap-3"
        >
            <a href="{{ route('home') }}" class="hover:text-ink transition"
                >← Trang chủ</a
            >
            <span class="text-gray-300">·</span>
            <a href="{{ route('faq.index') }}" class="hover:text-ink transition"
                >FAQ</a
            >
            <span class="text-gray-300">·</span>
            <a
                href="{{ route('blog.index') }}"
                class="hover:text-ink transition"
                >Blog</a
            >
        </div>
    </div>

    <footer class="py-4 text-center text-xs text-muted">
        © {{ date('Y') }} MTS — Đồ án tốt nghiệp
    </footer>
</body>
</html>
