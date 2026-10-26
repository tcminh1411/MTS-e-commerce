{{-- Mẫu giao diện danh sách đơn hàng phía cửa hàng. --}}
@extends ('layouts.storefront')
@section ('title', 'Đơn hàng của tôi')

@section ('content')
    <div class="max-w-5xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Đơn hàng của tôi</h1>

        @if ($orders->isEmpty())
            <div class="bg-white rounded-xl p-10 text-center text-gray-500">
                Bạn chưa có đơn hàng nào.
                <a
                    href="{{ route('products.index') }}"
                    class="text-indigo-600 hover:underline"
                    >Khám phá ngay →</a
                >
            </div>
        @else
            @php
            $statusLabels = [
                'pending'   => ['label' => 'Chờ xử lý',  'class' => 'bg-yellow-100 text-yellow-800'],
                'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'bg-blue-100 text-blue-800'],
                'shipping'  => ['label' => 'Đang giao',  'class' => 'bg-indigo-100 text-indigo-800'],
                'completed' => ['label' => 'Hoàn thành', 'class' => 'bg-green-100 text-green-800'],
                'cancelled' => ['label' => 'Đã hủy',     'class' => 'bg-red-100 text-red-800'],
            ];
            $paymentLabels = [
                'unpaid'   => ['label' => 'Chưa thanh toán', 'class' => 'bg-gray-100 text-gray-800'],
                'paid'     => ['label' => 'Đã thanh toán',   'class' => 'bg-green-100 text-green-800'],
                'refunded' => ['label' => 'Đã hoàn tiền',    'class' => 'bg-yellow-100 text-yellow-800'],
            ];
        @endphp

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr class="text-left text-gray-600">
                            <th class="px-4 py-3">Mã đơn</th>
                            <th class="px-4 py-3">Ngày đặt</th>
                            <th class="px-4 py-3 text-center">Số món</th>
                            <th class="px-4 py-3 text-right">Tổng tiền</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Thanh toán</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                            $st = $statusLabels[$order->status]  ?? ['label' => $order->status,         'class' => 'bg-gray-100 text-gray-800'];
                            $pm = $paymentLabels[$order->payment_status] ?? ['label' => $order->payment_status, 'class' => 'bg-gray-100 text-gray-800'];
                        @endphp
                            <tr class="border-b last:border-0 hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-medium">
                                    {{ $order->order_number }}
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-gray-600"
                                >
                                    {{ $order->placed_at?->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ $order->items_count }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right whitespace-nowrap font-medium"
                                >
                                    {{ number_format($order->total, 0, ',', '.') }} ₫
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $st['class'] }}"
                                    >
                                        {{ $st['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $pm['class'] }}"
                                    >
                                        {{ $pm['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @php
                                        $reviewableProductIds = $order->items
                                            ->pluck('product_id')
                                            ->filter()
                                            ->unique();
                                        $reviewedProductIds = $order->reviews->pluck('product_id');
                                        $needsReview = $order->status === 'completed'
                                            && $reviewableProductIds->diff($reviewedProductIds)->isNotEmpty();
                                    @endphp
                                    <a
                                        href="{{ route('orders.show', $order) }}{{ $needsReview ? '#order-products' : '' }}"
                                        class="inline-block text-indigo-600 hover:underline whitespace-nowrap"
                                    >
                                        {{ $needsReview ? 'Đánh giá sản phẩm' : 'Xem đơn hàng' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
