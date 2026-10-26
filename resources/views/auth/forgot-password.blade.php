{{-- Mẫu giao diện quên mật khẩu tài khoản. --}}
<x-guest-layout>
    <div class="text-center mb-6">
        <h1 class="font-serif text-2xl font-bold">Quên mật khẩu</h1>
        <p class="text-sm text-muted mt-1">Nhập email đã đăng ký — chúng tôi sẽ gửi link đặt lại mật khẩu.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form
        method="POST"
        action="{{ route('password.email') }}"
        class="space-y-4"
    >
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
                class="w-full border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-sage focus:ring-sage"
            />
            @error ('email')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full bg-sage hover:opacity-90 text-white font-semibold py-2.5 rounded-lg transition"
        >
            Gửi link đặt lại
        </button>
    </form>

    <div class="mt-6 pt-5 border-t text-center text-sm text-muted">
        <a
            href="{{ route('login') }}"
            class="text-ink font-semibold hover:text-sage transition"
        >
            ← Quay lại đăng nhập
        </a>
    </div>
</x-guest-layout>
