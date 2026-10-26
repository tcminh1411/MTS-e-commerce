{{-- Mẫu giao diện xác minh email tài khoản. --}}
<x-guest-layout>
    <div class="text-center mb-6">
        <h1 class="font-serif text-2xl font-bold">Xác minh email</h1>
        <p class="text-sm text-muted mt-2">Cảm ơn bạn đã đăng ký! Vui lòng kiểm tra hộp thư và bấm vào link xác minh mà chúng tôi vừa gửi.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-800 text-sm">
            Link xác minh mới đã được gửi tới email bạn đã đăng ký.
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button
                type="submit"
                class="w-full bg-sage hover:opacity-90 text-white font-semibold py-2.5 rounded-lg transition"
            >
                Gửi lại email xác minh
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full bg-bg hover:bg-gray-200 text-ink font-medium py-2.5 rounded-lg transition"
            >
                Đăng xuất
            </button>
        </form>
    </div>
</x-guest-layout>
