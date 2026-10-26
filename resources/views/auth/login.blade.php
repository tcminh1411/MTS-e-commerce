{{-- Mẫu giao diện đăng nhập tài khoản. --}}
<x-guest-layout>
    <div class="text-center mb-6">
        <h1 class="font-serif text-2xl font-bold">Đăng nhập</h1>
        <p class="text-sm text-muted mt-1">Chào mừng bạn quay lại MTS</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium mb-1"
                >Email</label
            >
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="w-full border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-sage focus:ring-sage"
            />
            @error ('email')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium mb-1"
                >Mật khẩu</label
            >
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="w-full border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-sage focus:ring-sage"
            />
            @error ('password')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    name="remember"
                    class="rounded border-gray-300 text-sage focus:ring-sage"
                />
                <span class="text-gray-600">Ghi nhớ đăng nhập</span>
            </label>

            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    class="text-terra hover:underline"
                    >Quên mật khẩu?</a
                >
            @endif
        </div>

        <button
            type="submit"
            class="w-full bg-sage hover:opacity-90 text-white font-semibold py-2.5 rounded-lg transition"
        >
            Đăng nhập
        </button>
    </form>

    <div class="mt-6 pt-5 border-t text-center text-sm text-muted">
        Chưa có tài khoản?
        <a
            href="{{ route('register') }}"
            class="text-ink font-semibold hover:text-sage transition"
        >
            Đăng ký ngay
        </a>
    </div>
</x-guest-layout>
