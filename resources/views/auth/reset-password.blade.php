{{-- Mẫu giao diện đặt lại mật khẩu tài khoản. --}}
<x-guest-layout>
    <div class="text-center mb-6">
        <h1 class="font-serif text-2xl font-bold">Đặt lại mật khẩu</h1>
        <p class="text-sm text-muted mt-1">Tạo mật khẩu mới cho tài khoản của bạn</p>
    </div>

    <form
        method="POST"
        action="{{ route('password.store') }}"
        class="space-y-4"
    >
        @csrf
        <input
            type="hidden"
            name="token"
            value="{{ $request->route('token') }}"
        />

        <div>
            <label for="email" class="block text-sm font-medium mb-1"
                >Email</label
            >
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $request->email) }}"
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
                >Mật khẩu mới</label
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
            Đặt lại mật khẩu
        </button>
    </form>
</x-guest-layout>
