{{-- Chi tiết yêu cầu trả hàng trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Chi tiết yêu cầu trả hàng #' . $return->id)
@section ('title', 'Chi tiết yêu cầu trả hàng')

@section ('content')
    <div class="mb-4">
        <a
            href="{{ route('admin.orders.returns.index') }}"
            class="text-sm text-gray-500 hover:text-gray-700"
            >← Danh sách yêu cầu</a
        >
    </div>

    @php
    $statusColors = [
        'pending'  => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-blue-100 text-blue-800',
        'rejected' => 'bg-red-100 text-red-800',
        'refunded' => 'bg-green-100 text-green-800',
    ];
    $st = $statusColors[$return->status] ?? 'bg-gray-100 text-gray-700';
@endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- LEFT: order + reason --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold">Đơn hàng liên quan</h2>
                    <span
                        class="px-2 py-0.5 rounded text-xs {{ $st }}"
                        >{{ $statuses[$return->status] ?? $return->status }}</span
                    >
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <div class="text-gray-500 text-xs">Mã đơn</div>
                        <a
                            href="{{ route('admin.orders.show', $return->order) }}"
                            class="font-mono text-indigo-600 hover:underline"
                        >
                            {{ $return->order->order_number }}
                        </a>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs">Tổng tiền</div>
                        <div class="font-medium">
                            {{ number_format($return->order->total, 0, ',', '.') }} ₫
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs">Khách hàng</div>
                        <div class="font-medium">
                            {{ $return->user?->full_name ?? 'Guest' }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $return->user?->email ?? '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs">Ngày đặt</div>
                        <div>
                            {{ $return->order->placed_at?->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t">
                    <h3 class="font-medium text-sm mb-2">Sản phẩm trong đơn</h3>
                    <table class="w-full text-xs">
                        <tbody>
                            @foreach ($return->order->items as $item)
                                <tr class="border-b last:border-0">
                                    <td class="py-1.5">
                                        {{ $item->product_name }}
                                        @if ($item->variant_name)
                                            <span class="text-gray-500"
                                                >({{ $item->variant_name }})</span
                                            >
                                        @endif
                                    </td>
                                    <td class="py-1.5 text-center">
                                        x{{ $item->quantity }}
                                    </td>
                                    <td
                                        class="py-1.5 text-right whitespace-nowrap"
                                    >
                                        {{ number_format($item->line_total, 0, ',', '.') }} ₫
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold mb-3">Lý do yêu cầu</h2>
                <p class="text-sm whitespace-pre-wrap">{{ $return->reason }}</p>
                <div class="text-xs text-gray-500 mt-3">
                    Gửi lúc: {{ $return->requested_at?->format('d/m/Y H:i') }}
                </div>
            </div>

            @if ($return->admin_note)
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h2 class="font-semibold mb-3">Phản hồi của shop</h2>
                    <p class="text-sm whitespace-pre-wrap">{{ $return->admin_note }}</p>
                    @if ($return->processedBy)
                        <div class="text-xs text-gray-500 mt-3">
                            Xử lý bởi: {{ $return->processedBy->full_name }}
                            @if ($return->processed_at) —{{ $return->processed_at->format('d/m/Y H:i') }} @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- RIGHT: actions --}}
        <div class="space-y-4">
            @if ($return->refund_amount)
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h2 class="font-semibold mb-3">Thông tin hoàn tiền</h2>
                    <div class="text-sm space-y-1">
                        <div>
                            <span class="text-gray-500">Số tiền:</span>
                            <span class="font-medium text-green-700"
                                >{{ number_format($return->refund_amount, 0, ',', '.') }} ₫</span
                            >
                        </div>
                        <div>
                            <span class="text-gray-500">Hình thức:</span>
                            {{ $refundMethods[$return->refund_method] ?? $return->refund_method }}
                        </div>
                        @if ($return->refunded_at)
                            <div>
                                <span class="text-gray-500">Đã hoàn lúc:</span>
                                {{ $return->refunded_at->format('d/m/Y H:i') }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($return->status === 'pending')
                {{-- APPROVE --}}
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h2 class="font-semibold mb-3 text-green-700">
                        ✔ Chấp nhận
                    </h2>
                    <form
                        method="POST"
                        action="{{ route('admin.orders.returns.approve', $return) }}"
                        class="space-y-3"
                    >
                        @csrf
                        @method ('PATCH')

                        <div>
                            <label
                                class="block text-xs font-medium text-gray-600 mb-1"
                                >Số tiền hoàn (₫) *</label
                            >
                            <input
                                type="number"
                                name="refund_amount"
                                value="{{ old('refund_amount', $return->order->total) }}"
                                min="0"
                                max="{{ $return->order->total }}"
                                required
                                class="w-full border rounded px-2 py-1.5 text-sm"
                            />
                            @error ('refund_amount')
                                <div class="text-red-600 text-xs mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label
                                class="block text-xs font-medium text-gray-600 mb-1"
                                >Hình thức *</label
                            >
                            <select
                                name="refund_method"
                                class="w-full border rounded px-2 py-1.5 text-sm"
                                required
                            >
                                @foreach ($refundMethods as $k => $v)
                                    <option
                                        value="{{ $k }}"
                                        @selected (old('refund_method') === $k)
                                    >
                                        {{ $v }}
                                    </option>
                                @endforeach
                            </select>
                            @error ('refund_method')
                                <div class="text-red-600 text-xs mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label
                                class="block text-xs font-medium text-gray-600 mb-1"
                                >Ghi chú (tuỳ chọn)</label
                            >
                            <textarea
                                name="admin_note"
                                rows="2"
                                maxlength="1000"
                                class="w-full border rounded px-2 py-1.5 text-sm"
                                >{{ old('admin_note') }}</textarea
                            >
                        </div>

                        <button
                            type="submit"
                            class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded text-sm"
                        >
                            Chấp nhận
                        </button>
                    </form>
                </div>

                {{-- REJECT --}}
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h2 class="font-semibold mb-3 text-red-700">✘ Từ chối</h2>
                    <form
                        method="POST"
                        action="{{ route('admin.orders.returns.reject', $return) }}"
                        class="space-y-3"
                    >
                        @csrf
                        @method ('PATCH')

                        <div>
                            <label
                                class="block text-xs font-medium text-gray-600 mb-1"
                                >Lý do từ chối *</label
                            >
                            <textarea
                                name="admin_note"
                                rows="3"
                                required
                                minlength="5"
                                maxlength="1000"
                                class="w-full border rounded px-2 py-1.5 text-sm"
                                >{{ old('admin_note') }}</textarea
                            >
                            @error ('admin_note')
                                <div class="text-red-600 text-xs mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm"
                        >
                            Từ chối
                        </button>
                    </form>
                </div>
            @endif

            @if ($return->status === 'approved')
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h2 class="font-semibold mb-3 text-blue-700">
                        Đánh dấu đã hoàn tiền
                    </h2>
                    <p class="text-xs text-gray-500 mb-3">Sau khi chuyển khoản / hoàn tiền mặt cho khách, bấm nút dưới để đánh dấu đơn đã hoàn.</p>
                    <form
                        method="POST"
                        action="{{ route('admin.orders.returns.markRefunded', $return) }}"
                    >
                        @csrf
                        @method ('PATCH')
                        <button
                            type="submit"
                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm"
                        >
                            Đã hoàn tiền
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

@endsection
