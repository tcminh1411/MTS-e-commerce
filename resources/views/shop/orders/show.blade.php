{{-- Mẫu giao diện chi tiết đơn hàng phía cửa hàng. --}}
@extends ('layouts.storefront')
@section ('title', 'Đơn hàng ' . $order->order_number)

@section ('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-4">
            <a
                href="{{ route('orders.index') }}"
                class="text-indigo-600 hover:underline text-sm"
                >← Danh sách đơn hàng</a
            >
        </div>

        @php
    $statusLabels = [
    'pending' => ['label' => 'Chờ xử lý', 'class' => 'bg-yellow-100 text-yellow-800'],
    'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'bg-blue-100 text-blue-800'],
    'shipping' => ['label' => 'Đang giao', 'class' => 'bg-indigo-100 text-indigo-800'],
    'completed' => ['label' => 'Hoàn thành', 'class' => 'bg-green-100 text-green-800'],
    'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-red-100 text-red-800'],
    ];
    $paymentLabels = [
    'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-gray-100 text-gray-800'],
    'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-green-100 text-green-800'],
    'refunded' => ['label' => 'Đã hoàn tiền', 'class' => 'bg-yellow-100 text-yellow-800'],
    ];
    $st = $statusLabels[$order->status] ?? ['label' => $order->status, 'class' => 'bg-gray-100 text-gray-800'];
    $pm = $paymentLabels[$order->payment_status] ?? ['label' => $order->payment_status, 'class' => 'bg-gray-100
    text-gray-800'];
    @endphp

        {{-- Header --}}
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-sm text-gray-500">Mã đơn hàng</div>
                    <div class="text-xl font-mono font-bold">
                        {{ $order->order_number }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        Đặt lúc: {{ $order->placed_at?->format('d/m/Y H:i') }}
                    </div>
                </div>
                <div class="flex flex-col gap-2 items-end">
                    <span
                        class="inline-block px-3 py-1 rounded text-sm font-medium {{ $st['class'] }}"
                    >
                        {{ $st['label'] }}
                    </span>
                    <span
                        class="inline-block px-3 py-1 rounded text-sm font-medium {{ $pm['class'] }}"
                    >
                        {{ $pm['label'] }}
                    </span>
                    @if ($canCancel)
                        <form
                            method="POST"
                            action="{{ route('orders.cancel', $order) }}"
                            onsubmit="
                                return confirm(
                                    'Bạn có chắc muốn hủy đơn hàng này?',
                                );
                            "
                        >
                            @csrf
                            <button
                                type="submit"
                                class="rounded border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-100"
                            >
                                Hủy đơn hàng
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if ($order->status === 'completed')
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                <p class="text-sm text-indigo-900">
                    Đơn hàng đã hoàn thành. Chia sẻ cảm nhận của bạn về sản phẩm nhé.
                </p>
                <a
                    href="#order-products"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Đánh giá sản phẩm
                </a>
            </div>
        @endif

        {{-- Người nhận + Thanh toán --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow p-6">
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

            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-semibold mb-3">
                    Thanh toán & Vận chuyển
                </h2>
                <dl class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500">Phương thức:</span>
                        <span class="font-medium">
                            @if ($order->payment_method === 'cod')
                                COD
                            @elseif ($order->payment_method === 'vnpay')
                                VNPay
                            @else
                                {{ $order->payment_method }}
                            @endif
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

        {{-- Items --}}
        <div id="order-products" class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Sản phẩm</h2>
            @if ($order->status !== 'completed')
                <p class="mb-4 text-sm text-gray-500">
                    Bạn có thể đánh giá sản phẩm sau khi đơn hàng hoàn thành.
                </p>
            @endif
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
                    @php
                    $existingReviews = $order->reviews->keyBy('product_id');
                @endphp
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
                                @if ($order->status === 'completed' && $item->product_id)
                                    @php $rev = $existingReviews[$item->product_id] ?? null; @endphp
                                    @if ($rev)
                                        @if ($rev->is_visible)
                                            <div
                                                class="text-yellow-500 text-xs mt-1"
                                            >
                                                {{ $rev->stars }}
                                                <span class="text-gray-500"
                                                    >(đã đánh giá)</span
                                                >
                                            </div>
                                        @else
                                            <div
                                                class="text-red-500 text-xs mt-1"
                                            >
                                                Đánh giá đã bị ẩn
                                            </div>
                                        @endif
                                    @else
                                        <a
                                            href="{{ route('orders.reviews.create', ['order' => $order, 'product_id' => $item->product_id]) }}"
                                            class="mt-2 inline-flex items-center rounded-md bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                                        >
                                            ⭐ Viết đánh giá
                                        </a>
                                    @endif
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
                        <td class="pt-2 text-right text-indigo-700">
                            {{ number_format($order->total, 0, ',', '.') }} ₫
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Timeline --}}
        @if ($order->statusHistory->isNotEmpty())
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Lịch sử trạng thái</h2>
                <ol class="relative border-l-2 border-gray-200 ml-2">
                    @foreach ($order->statusHistory as $h)
                        @php
            $hSt = $statusLabels[$h->to_status] ?? ['label' => $h->to_status, 'class' => 'bg-gray-100 text-gray-800'];
            @endphp
                        <li class="ml-6 mb-5 last:mb-0">
                            <span
                                class="absolute -left-[9px] mt-1 w-4 h-4 rounded-full bg-indigo-500 border-2 border-white"
                            ></span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $hSt['class'] }}"
                                >
                                    {{ $hSt['label'] }}
                                </span>
                                <span
                                    class="text-xs text-gray-500"
                                    >{{ $h->created_at->format('d/m/Y H:i') }}</span
                                >
                            </div>
                            @if ($h->note)
                                <div class="text-sm text-gray-700 mt-1">
                                    {{ $h->note }}
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if ($order->note)
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-2">Ghi chú</h2>
                <p class="text-sm text-gray-700">{{ $order->note }}</p>
            </div>
        @endif
        {{-- Yêu cầu trả hàng: chỉ hiện khi đơn completed và chưa có return --}}
        @if ($order->status === 'completed' && ! $order->returnRequest)
            <div class="bg-white rounded-xl shadow p-6 mb-6 text-center">
                <p class="text-sm text-gray-600 mb-3">Có vấn đề với đơn hàng?</p>
                <a
                    href="{{ route('orders.return.create', $order) }}"
                    class="inline-block px-5 py-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded"
                >
                    ↩ Yêu cầu trả hàng
                </a>
            </div>
        @endif

        {{-- Trạng thái return nếu đã có --}}
        @if ($order->returnRequest)
            @php
    $rLabels = [
    'pending' => ['Chờ xử lý', 'bg-yellow-100 text-yellow-800'],
    'approved' => ['Đã chấp nhận', 'bg-blue-100 text-blue-800'],
    'rejected' => ['Đã từ chối', 'bg-red-100 text-red-800'],
    'refunded' => ['Đã hoàn tiền', 'bg-green-100 text-green-800'],
    ];
    $rInfo = $rLabels[$order->returnRequest->status] ?? [$order->returnRequest->status, 'bg-gray-100 text-gray-800'];
    @endphp
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-3">Yêu cầu trả hàng</h2>
                <div class="text-sm space-y-2">
                    <div>
                        <span class="text-gray-500">Trạng thái:</span>
                        <span
                            class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $rInfo[1] }}"
                        >
                            {{ $rInfo[0] }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500">Lý do:</span>
                        <span
                            class="text-gray-800"
                            >{{ $order->returnRequest->reason }}</span
                        >
                    </div>
                    @if ($order->returnRequest->admin_note)
                        <div>
                            <span class="text-gray-500">Phản hồi từ shop:</span>
                            <span
                                class="text-gray-800"
                                >{{ $order->returnRequest->admin_note }}</span
                            >
                        </div>
                    @endif
                    @if ($order->returnRequest->refund_amount)
                        <div>
                            <span class="text-gray-500">Số tiền hoàn:</span>
                            <span class="font-medium text-green-700"
                                >{{ number_format($order->returnRequest->refund_amount, 0, ',', '.') }} ₫</span
                            >
                        </div>
                    @endif
                </div>
            </div>
        @endif
        <div class="flex flex-wrap gap-3 justify-center mt-6">
            <a
                href="{{ route('orders.invoice', $order) }}"
                target="_blank"
                class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded"
            >
                🖨 In hóa đơn
            </a>
            <a
                href="{{ route('products.index') }}"
                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded"
            >
                Tiếp tục mua sắm
            </a>
        </div>
    </div>
@endsection
