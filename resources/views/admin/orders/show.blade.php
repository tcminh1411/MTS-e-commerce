{{-- Mẫu giao diện chi tiết đơn hàng trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Đơn ' . $order->order_number)
@section ('title', 'Chi tiết đơn hàng')

@section ('content')
    {{-- ==================== HEADER ==================== --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mb-4">
        <div
            class="flex flex-col md:flex-row md:items-center md:justify-between gap-3"
        >
            <div>
                <h1 class="text-xl font-bold font-mono text-indigo-700">
                    {{ $order->order_number }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Đặt lúc {{ $order->created_at->format('d/m/Y H:i') }}
                    @if ($order->placed_at)
                        · Ghi nhận: {{ $order->placed_at->format('d/m/Y H:i') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                @php
            $statusColors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'confirmed' => 'bg-blue-100 text-blue-800',
            'shipping' => 'bg-indigo-100 text-indigo-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            ];
            $payColors = [
            'unpaid' => 'bg-gray-100 text-gray-700',
            'paid' => 'bg-green-100 text-green-800',
            'refunded' => 'bg-orange-100 text-orange-800',
            ];
            @endphp
                <span
                    class="px-3 py-1 rounded text-sm {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}"
                >
                    {{ $statuses[$order->status] ?? $order->status }}
                </span>
                <span
                    class="px-3 py-1 rounded text-sm {{ $payColors[$order->payment_status] ?? 'bg-gray-100 text-gray-700' }}"
                >
                    {{ $paymentStatuses[$order->payment_status] ?? $order->payment_status }}
                </span>
                <a
                    href="{{ route('admin.orders.index') }}"
                    class="text-sm text-gray-500 hover:text-gray-700"
                    >← Danh sách</a
                >
                <a
                    href="{{ route('admin.orders.invoice', $order) }}"
                    target="_blank"
                    class="ml-3 text-sm text-indigo-600 hover:text-indigo-800"
                >
                    🖨 In hóa đơn
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- ==================== CỘT TRÁI (2/3) ==================== --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Thông tin khách hàng --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold mb-3">Thông tin người nhận</h2>
                <dl
                    class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-sm"
                >
                    <div>
                        <dt class="text-gray-500">Họ tên</dt>
                        <dd class="font-medium">
                            {{ $order->recipient_name }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Số điện thoại</dt>
                        <dd class="font-medium">
                            {{ $order->recipient_phone }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Email</dt>
                        <dd class="font-medium">
                            {{ $order->recipient_email ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tài khoản</dt>
                        <dd class="font-medium">
                            @if ($order->user)
                                {{ $order->user->full_name }}
                                <span class="text-gray-400"
                                    >({{ $order->user->email }})</span
                                >
                            @else
                                Khách vãng lai
                            @endif
                        </dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-gray-500">Địa chỉ giao hàng</dt>
                        <dd class="font-medium">
                            {{ $order->shipping_address }}
                        </dd>
                    </div>
                    @if ($order->note)
                        <div class="md:col-span-2">
                            <dt class="text-gray-500">Ghi chú</dt>
                            <dd class="italic text-gray-700">
                                {{ $order->note }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Sản phẩm --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b">
                    <h2 class="font-semibold">
                        Sản phẩm ({{ $order->items->count() }})
                    </h2>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Sản phẩm</th>
                            <th class="px-4 py-3 text-left">SKU</th>
                            <th class="px-4 py-3 text-right">Đơn giá</th>
                            <th class="px-4 py-3 text-center">SL</th>
                            <th class="px-4 py-3 text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ $item->product_name }}
                                    </div>
                                    @if ($item->variant_name)
                                        <div class="text-xs text-gray-500">
                                            Phân loại: {{ $item->variant_name }}
                                        </div>
                                    @endif
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-xs text-gray-500"
                                >
                                    {{ $item->sku ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ number_format($item->unit_price, 0, ',', '.') }} ₫
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ $item->quantity }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    {{ number_format($item->line_total, 0, ',', '.') }} ₫
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 text-sm">
                        <tr>
                            <td
                                colspan="4"
                                class="px-4 py-2 text-right text-gray-600"
                            >
                                Tạm tính:
                            </td>
                            <td class="px-4 py-2 text-right">
                                {{ number_format($order->subtotal, 0, ',', '.') }} ₫
                            </td>
                        </tr>
                        @if ($order->discount_amount > 0)
                            <tr>
                                <td
                                    colspan="4"
                                    class="px-4 py-2 text-right text-red-600"
                                >
                                    Giảm giá
                                    @if ($order->coupon_code) ({{ $order->coupon_code }}) @endif
                                    :
                                </td>
                                <td class="px-4 py-2 text-right text-red-600">
                                    -{{ number_format($order->discount_amount, 0, ',', '.') }} ₫
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td
                                colspan="4"
                                class="px-4 py-2 text-right text-gray-600"
                            >
                                Phí vận chuyển
                                @if ($order->shipping_rate_name)
                                    <span class="text-xs"
                                        >({{ $order->shipping_rate_name }})</span
                                    >
                                @endif
                                :
                            </td>
                            <td class="px-4 py-2 text-right">
                                {{ number_format($order->shipping_fee, 0, ',', '.') }} ₫
                            </td>
                        </tr>
                        <tr class="border-t">
                            <td
                                colspan="4"
                                class="px-4 py-3 text-right font-semibold"
                            >
                                Tổng cộng:
                            </td>
                            <td
                                class="px-4 py-3 text-right font-bold text-indigo-700 text-base"
                            >
                                {{ number_format($order->total, 0, ',', '.') }} ₫
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Vận chuyển + thanh toán --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold mb-3">Vận chuyển & Thanh toán</h2>
                <dl
                    class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-sm"
                >
                    <div>
                        <dt class="text-gray-500">Khu vực</dt>
                        <dd class="font-medium">
                            {{ $order->shippingZone?->name ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Phương thức vận chuyển</dt>
                        <dd class="font-medium">
                            {{ $order->shipping_rate_name ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Phương thức thanh toán</dt>
                        <dd class="font-medium uppercase">
                            {{ $order->payment_method }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Trạng thái thanh toán</dt>
                        <dd class="font-medium">
                            {{ $paymentStatuses[$order->payment_status] ?? $order->payment_status }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- ==================== CỘT PHẢI (1/3) ==================== --}}
        <div class="space-y-4">
            {{-- Form đổi trạng thái --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold mb-3">Cập nhật trạng thái</h2>
                <form
                    method="POST"
                    action="{{ route('admin.orders.updateStatus', $order) }}"
                    x-data="{ toStatus: @js(old('to_status', $order->status)) }"
                >
                    @csrf
                    @method ('PATCH')

                    <label class="block text-sm font-medium mb-1"
                        >Trạng thái mới</label
                    >
                    <select
                        name="to_status"
                        x-model="toStatus"
                        class="w-full border rounded px-2 py-1.5 text-sm mb-3"
                    >
                        @foreach ($statuses as $key => $label)
                            <option
                                value="{{ $key }}"
                                @selected ($order->status === $key)
                            >
                                {{ $label }}
                                @if ($order->status === $key) (hiện tại)@endif
                            </option>
                        @endforeach
                    </select>
                    @error ('to_status')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror

                    <div
                        x-show="toStatus === 'shipping'"
                        x-cloak
                        class="mb-3"
                        style="display: none"
                    >
                        <label
                            for="warehouse-id"
                            class="block text-sm font-medium mb-1"
                            >Kho xuất hàng</label
                        >
                        <select
                            id="warehouse-id"
                            name="warehouse_id"
                            :required="toStatus === 'shipping' && @js($order->status !== 'shipping')"
                            class="w-full border rounded px-2 py-1.5 text-sm"
                        >
                            <option value="">Chọn kho</option>
                            @foreach ($warehouses as $warehouse)
                                <option
                                    value="{{ $warehouse->id }}"
                                    @selected ((string) old('warehouse_id') === (string) $warehouse->id)
                                >
                                    {{ $warehouse->name }} ({{ $warehouse->code }})
                                </option>
                            @endforeach
                        </select>
                        @if ($warehouses->isEmpty())
                            <p class="text-red-600 text-xs mt-1">
                                Chưa có kho nào đang hoạt động.
                            </p>
                        @endif
                        @error ('warehouse_id')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="block text-sm font-medium mb-1"
                        >Ghi chú (tuỳ chọn)</label
                    >
                    <textarea
                        name="note"
                        rows="3"
                        maxlength="500"
                        class="w-full border rounded px-2 py-1.5 text-sm mb-3"
                        placeholder="VD: Đã gọi xác nhận với khách..."
                        >{{ old('note') }}</textarea
                    >
                    @error ('note')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror

                    <button
                        type="submit"
                        class="w-full px-3 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700"
                    >
                        Cập nhật
                    </button>
                </form>
            </div>

            {{-- Form đổi payment status --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold mb-3">Cập nhật thanh toán</h2>
                @if (auth()->user()->hasRole('admin'))
                    <form
                        method="POST"
                        action="{{ route('admin.orders.updatePaymentStatus', $order) }}"
                    >
                        @csrf
                        @method ('PATCH')

                        <label class="block text-sm font-medium mb-1"
                            >Trạng thái thanh toán</label
                        >
                        <select
                            name="payment_status"
                            class="w-full border rounded px-2 py-1.5 text-sm mb-3"
                        >
                            @foreach ($paymentStatuses as $key => $label)
                                <option
                                    value="{{ $key }}"
                                    @selected ($order->payment_status === $key)
                                >
                                    {{ $label }}
                                    @if ($order->payment_status === $key)
                                        (hiện tại)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error ('payment_status')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror

                        <label class="block text-sm font-medium mb-1"
                            >Ghi chú (tuỳ chọn)</label
                        >
                        <textarea
                            name="note"
                            rows="2"
                            maxlength="500"
                            class="w-full border rounded px-2 py-1.5 text-sm mb-3"
                            placeholder="VD: Đã nhận tiền COD từ shipper..."
                            >{{ old('note') }}</textarea
                        >
                        @error ('note')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror

                        <button
                            type="submit"
                            class="w-full px-3 py-2 rounded bg-green-600 text-white text-sm hover:bg-green-700"
                        >
                            Cập nhật thanh toán
                        </button>
                    </form>
                @else
                    <div class="text-sm text-gray-500 italic">
                        Chỉ admin có quyền đổi trạng thái thanh toán.<br />
                        Hiện tại:
                        <strong
                            class="not-italic text-gray-800"
                            >{{ $paymentStatuses[$order->payment_status] ?? $order->payment_status }}</strong
                        >
                    </div>
                @endif
            </div>

            {{-- Timeline status --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold mb-3">Lịch sử trạng thái</h2>
                <ol class="relative border-l border-gray-200 ml-2 space-y-4">
                    @forelse ($order->statusHistory as $h)
                        @php $isPayment = str_starts_with($h->note ?? '', '[Payment]'); @endphp
                        <li class="ml-4">
                            <span
                                class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full {{ $isPayment ? 'bg-green-500' : 'bg-indigo-500' }}"
                            ></span>
                            <div class="text-sm">
                                @if ($isPayment)
                                    <div class="font-medium text-green-700">
                                        💳 Thanh toán
                                    </div>
                                @else
                                    <div class="font-medium">
                                        @if ($h->from_status)
                                            {{ $statuses[$h->from_status] ?? $h->from_status }} →
                                        @endif
                                        <span
                                            class="text-indigo-700"
                                            >{{ $statuses[$h->to_status] ?? $h->to_status }}</span
                                        >
                                    </div>
                                @endif
                                <div class="text-xs text-gray-500">
                                    {{ $h->created_at->format('d/m/Y H:i') }}
                                    @if ($h->changedBy)
                                        · {{ $h->changedBy->full_name }}
                                    @endif
                                </div>
                                @if ($h->note)
                                    <div
                                        class="text-xs mt-1 {{ $isPayment ? 'text-green-700' : 'italic text-gray-600' }}"
                                    >
                                        {{ $isPayment ? substr($h->note, 10) : $h->note }}
                                    </div>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="ml-4 text-sm text-gray-400">
                            Chưa có lịch sử.
                        </li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>

@endsection
