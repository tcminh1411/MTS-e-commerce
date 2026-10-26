{{-- Biểu mẫu yêu cầu trả hàng cho đơn hàng. --}}
@extends ('layouts.storefront')
@section ('title', 'Yêu cầu trả hàng - ' . $order->order_number)

@section ('content')
    <div class="max-w-2xl mx-auto">
        <div class="mb-4">
            <a
                href="{{ route('orders.show', $order) }}"
                class="text-indigo-600 hover:underline text-sm"
            >
                ← Chi tiết đơn hàng
            </a>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h1 class="text-xl font-bold mb-2">Yêu cầu trả hàng</h1>
            <p class="text-sm text-gray-600 mb-6">
                Đơn hàng:
                <span
                    class="font-mono font-medium"
                    >{{ $order->order_number }}</span
                >
                &nbsp;|&nbsp; Tổng:
                <span class="font-medium"
                    >{{ number_format($order->total, 0, ',', '.') }} ₫</span
                >
            </p>

            <form
                method="POST"
                action="{{ route('orders.return.store', $order) }}"
            >
                @csrf

                <label for="reason" class="block font-medium mb-2">
                    Lý do trả hàng <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="reason"
                    name="reason"
                    rows="6"
                    maxlength="2000"
                    required
                    placeholder="Ví dụ: Sản phẩm bị lỗi, giao sai mẫu, không đúng mô tả..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('reason') border-red-500 @enderror"
                    >{{ old('reason') }}</textarea
                >

                @error ('reason')
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror

                <div class="text-xs text-gray-500 mt-2">
                    Tối thiểu 10 ký tự, tối đa 2000 ký tự.
                </div>

                <div class="flex gap-3 mt-6">
                    <button
                        type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded"
                    >
                        Gửi yêu cầu
                    </button>
                    <a
                        href="{{ route('orders.show', $order) }}"
                        class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded"
                    >
                        Hủy
                    </a>
                </div>
            </form>

            <div
                class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800"
            >
                💡 Sau khi gửi, yêu cầu sẽ ở trạng thái
                <strong>Chờ xử lý</strong>. Bộ phận CSKH sẽ liên hệ với bạn qua
                số điện thoại đã cung cấp.
            </div>
        </div>
    </div>
@endsection
