{{-- Xác nhận đơn hàng sau khi đặt thành công. --}}
@extends ('layouts.storefront')

@section ('title', 'Đặt hàng thành công')

@section ('content')
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div
            class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6 text-center"
        >
            <div class="text-5xl mb-3">✅</div>
            <h1 class="text-2xl font-bold text-green-800 mb-2">
                Đặt hàng thành công!
            </h1>
            <p class="text-gray-700">
                Mã đơn hàng của bạn:
                <span
                    class="font-mono font-bold text-lg"
                    >{{ $order->order_number }}</span
                >
            </p>
            <p class="text-sm text-gray-600 mt-2">
                Đặt lúc: {{ $order->placed_at?->format('d/m/Y H:i') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            {{-- Thông tin người nhận --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-3">Người nhận</h2>
                <dl class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500">Họ tên:</span>
                        <span
                            class="font-medium"
                            >{{ $order->recipient_name }}</span
                        >
                    </div>
                    <div>
                        <span class="text-gray-500">SĐT:</span>
                        <span
                            class="font-medium"
                            >{{ $order->recipient_phone }}</span
                        >
                    </div>
                    @if ($order->recipient_email)
                        <div>
                            <span class="text-gray-500">Email:</span>
                            <span
                                class="font-medium"
                                >{{ $order->recipient_email }}</span
                            >
                        </div>
                    @endif
                    <div>
                        <span class="text-gray-500">Địa chỉ:</span>
                        <span
                            class="font-medium"
                            >{{ $order->shipping_address }}</span
                        >
                    </div>
                </dl>
            </div>

            {{-- Thông tin thanh toán + vận chuyển --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-3">
                    Thanh toán & Vận chuyển
                </h2>
                <dl class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500">Phương thức:</span>
                        <span class="font-medium">
                            @if ($order->payment_method === 'cod')
                                Thanh toán khi nhận hàng (COD)
                            @elseif ($order->payment_method === 'vnpay')
                                VNPay
                            @else
                                {{ $order->payment_method }}
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500">Trạng thái:</span>
                        <span
                            class="inline-block px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 text-xs font-medium"
                        >
                            {{ $order->status }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500">Thanh toán:</span>
                        <span
                            class="inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-800 text-xs font-medium"
                        >
                            {{ $order->payment_status }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500">Vận chuyển:</span>
                        <span
                            class="font-medium"
                            >{{ $order->shipping_rate_name }}</span
                        >
                    </div>
                    @if ($order->shippingZone)
                        <div>
                            <span class="text-gray-500">Khu vực:</span>
                            <span
                                class="font-medium"
                                >{{ $order->shippingZone->name }}</span
                            >
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Chi tiết items --}}
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Sản phẩm</h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="pb-2">Sản phẩm</th>
                        <th class="pb-2 text-right">Đơn giá</th>
                        <th class="pb-2 text-center">SL</th>
                        <th class="pb-2 text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr class="border-b last:border-0">
                            <td class="py-2">
                                <div class="font-medium">
                                    {{ $item->product_name }}
                                </div>
                                @if ($item->variant_name)
                                    <div class="text-gray-500 text-xs">
                                        {{ $item->variant_name }}
                                    </div>
                                @endif
                                @if ($item->sku)
                                    <div class="text-gray-400 text-xs">
                                        SKU: {{ $item->sku }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-2 text-right whitespace-nowrap">
                                {{ number_format($item->unit_price, 0, ',', '.') }} ₫
                            </td>
                            <td class="py-2 text-center">
                                {{ $item->quantity }}
                            </td>
                            <td
                                class="py-2 text-right whitespace-nowrap font-medium"
                            >
                                {{ number_format($item->line_total, 0, ',', '.') }} ₫
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="text-sm">
                    <tr>
                        <td colspan="3" class="pt-3 text-right text-gray-600">
                            Tạm tính:
                        </td>
                        <td class="pt-3 text-right">
                            {{ number_format($order->subtotal, 0, ',', '.') }} ₫
                        </td>
                    </tr>
                    @if ($order->discount_amount > 0)
                        <tr>
                            <td colspan="3" class="text-right text-gray-600">
                                Giảm giá:
                            </td>
                            <td class="text-right text-red-600">
                                -{{ number_format($order->discount_amount, 0, ',', '.') }} ₫
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="3" class="text-right text-gray-600">
                            Phí vận chuyển:
                        </td>
                        <td class="text-right">
                            {{ number_format($order->shipping_fee, 0, ',', '.') }} ₫
                        </td>
                    </tr>
                    <tr class="text-base font-bold">
                        <td colspan="3" class="pt-2 text-right">Tổng cộng:</td>
                        <td class="pt-2 text-right text-blue-700">
                            {{ number_format($order->total, 0, ',', '.') }} ₫
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($order->note)
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-2">Ghi chú</h2>
                <p class="text-sm text-gray-700">{{ $order->note }}</p>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-wrap gap-3 justify-center">
            <a
                href="{{ route('home') }}"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded"
            >
                Về trang chủ
            </a>
            <a
                href="{{ route('products.index') }}"
                class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded"
            >
                Tiếp tục mua sắm
            </a>
            @auth
                {{-- Route orders.index sẽ có ở Ngày 4 — bọc Route::has để tránh lỗi --}}
                @if (Route::has('orders.index'))
                    <a
                        href="{{ route('orders.index') }}"
                        class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded"
                    >
                        Đơn hàng của tôi
                    </a>
                @endif
            @endauth
        </div>

        @guest
            <div
                class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800 text-center"
            >
                💡 Bạn đang đặt hàng với tư cách khách. Hãy lưu lại mã đơn
                <span
                    class="font-mono font-bold"
                    >{{ $order->order_number }}</span
                >
                để tra cứu sau này.
            </div>
        @endguest
    </div>
@endsection
