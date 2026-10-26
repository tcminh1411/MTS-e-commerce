{{-- Mẫu giao diện xác nhận mật khẩu tài khoản. --}}
<x-guest-layout>
    <div class="text-center mb-6">
        <h1 class="font-serif text-2xl font-bold">Xác nhận mật khẩu</h1>
        <p class="text-sm text-muted mt-1">Đây là khu vực bảo mật. Vui lòng xác nhận mật khẩu trước khi tiếp tục.</p>
    </div>

    <form
        method="POST"
        action="{{ route('password.confirm') }}"
        class="space-y-4"
    >
        @csrf

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
                autofocus
                class="w-full border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:border-sage focus:ring-sage"
            />
            @error ('password')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full bg-sage hover:opacity-90 text-white font-semibold py-2.5 rounded-lg transition"
        >
            Xác nhận
        </button>
    </form>
</x-guest-layout>
