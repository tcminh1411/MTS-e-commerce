{{-- Mẫu giao diện đăng ký tài khoản. --}}
<x-guest-layout>
    <div class="text-center mb-6">
        <h1 class="font-serif text-2xl font-bold">Đăng ký</h1>
        <p class="text-sm text-muted mt-1">Tạo tài khoản miễn phí tại MTS</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="full_name" class="block text-sm font-medium mb-1"
                >Họ và tên</label
            >
            <input
                id="full_name"
                name="full_name"
                type="text"
                value="{{ old('full_name') }}"
                required
                autofocus
                autocomplete="name"
                class="w-full border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-sage focus:ring-sage"
            />
            @error ('full_name')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

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
                autocomplete="new-password"
                class="w-full border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-sage focus:ring-sage"
            />
            @error ('password')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label
                for="password_confirmation"
                class="block text-sm font-medium mb-1"
                >Xác nhận mật khẩu</label
            >
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                class="w-full border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-sage focus:ring-sage"
            />
        </div>

        <button
            type="submit"
            class="w-full bg-sage hover:opacity-90 text-white font-semibold py-2.5 rounded-lg transition"
        >
            Tạo tài khoản
        </button>
    </form>

    <div class="mt-6 pt-5 border-t text-center text-sm text-muted">
        Đã có tài khoản?
        <a
            href="{{ route('login') }}"
            class="text-ink font-semibold hover:text-sage transition"
        >
            Đăng nhập
        </a>
    </div>
</x-guest-layout>
