{{-- Mẫu giao diện danh sách đơn hàng trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Đơn hàng')
@section ('title', 'Đơn hàng')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách đơn hàng
                <span class="text-gray-500">({{ $orders->total() }})</span>
            </h2>
        </div>

        {{-- ==================== FILTER ==================== --}}
        <div class="rounded-lg bg-white p-4 shadow">
            <form
                method="GET"
                action="{{ route('admin.orders.index') }}"
                class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end"
            >
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1"
                        >Trạng thái đơn</label
                    >
                    <select
                        name="status"
                        class="w-full border rounded px-2 py-1.5 text-sm w-60"
                    >
                        <option value="">Tất cả</option>
                        @foreach ($statuses as $key => $label)
                            <option
                                value="{{ $key }}"
                                @selected (($filters['status'] ?? '' )===$key)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1"
                        >Thanh toán</label
                    >
                    <select
                        name="payment_status"
                        class="w-full border rounded px-2 py-1.5 text-sm w-60"
                    >
                        <option value="">Tất cả</option>
                        @foreach ($paymentStatuses as $key => $label)
                            <option
                                value="{{ $key }}"
                                @selected (($filters['payment_status'] ?? '' )===$key)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1"
                        >Tìm kiếm</label
                    >
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Mã đơn / SĐT / Tên / Email"
                        class="w-full border rounded px-2 py-1.5 text-sm"
                    />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1"
                        >Từ ngày</label
                    >
                    <input
                        type="date"
                        name="date_from"
                        value="{{ $filters['date_from'] ?? '' }}"
                        class="w-full border rounded px-2 py-1.5 text-sm w-60"
                    />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1"
                        >Đến ngày</label
                    >
                    <input
                        type="date"
                        name="date_to"
                        value="{{ $filters['date_to'] ?? '' }}"
                        class="w-full border rounded px-2 py-1.5 text-sm w-60"
                    />
                </div>

                <div class="md:col-span-1 flex gap-2">
                    <button
                        type="submit"
                        class="w-full px-3 py-1.5 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700"
                    >
                        Lọc
                    </button>
                </div>
            </form>

            @if (array_filter($filters ?? []))
                <div class="mt-2 text-sm">
                    <a
                        href="{{ route('admin.orders.index') }}"
                        class="text-indigo-600 hover:underline"
                        >✕ Xoá bộ lọc</a
                    >
                </div>
            @endif
        </div>

        {{-- ==================== TABLE ==================== --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Mã đơn</th>
                            <th class="px-4 py-3 text-left">Khách hàng</th>
                            <th class="px-4 py-3 text-right">Tổng tiền</th>
                            <th class="px-4 py-3 text-center">Trạng thái</th>
                            <th class="px-4 py-3 text-center">Thanh toán</th>
                            <th class="px-4 py-3 text-center">Ngày tạo</th>
                            <th class="px-4 py-3 text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($orders as $o)
                            <tr>
                                <td
                                    class="px-4 py-3 font-mono font-medium text-indigo-700"
                                >
                                    {{ $o->order_number }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ $o->recipient_name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $o->recipient_phone }}
                                    </div>
                                    @if ($o->user)
                                        <div class="text-xs text-gray-400">
                                            TK: {{ $o->user->email }}
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-400">
                                            Khách vãng lai
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    {{ number_format($o->total, 0, ',', '.') }} ₫
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php
                        $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'confirmed' => 'bg-blue-100 text-blue-800',
                        'shipping' => 'bg-indigo-100 text-indigo-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                        ];
                        @endphp
                                    <span
                                        class="px-2 py-0.5 rounded text-xs {{ $statusColors[$o->status] ?? 'bg-gray-100 text-gray-700' }}"
                                    >
                                        {{ $statuses[$o->status] ?? $o->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php
                        $payColors = [
                        'unpaid' => 'bg-gray-100 text-gray-700',
                        'paid' => 'bg-green-100 text-green-800',
                        'refunded' => 'bg-orange-100 text-orange-800',
                        ];
                        @endphp
                                    <span
                                        class="px-2 py-0.5 rounded text-xs {{ $payColors[$o->payment_status] ?? 'bg-gray-100 text-gray-700' }}"
                                    >
                                        {{ $paymentStatuses[$o->payment_status] ?? $o->payment_status }}
                                    </span>
                                    <div
                                        class="text-xs text-gray-500 mt-1 uppercase"
                                    >
                                        {{ $o->payment_method }}
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 text-center text-gray-500 text-xs"
                                >
                                    {{ $o->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right whitespace-nowrap"
                                >
                                    <a
                                        href="{{ route('admin.orders.show', $o) }}"
                                        class="text-indigo-600 hover:underline"
                                        >Xem</a
                                    >
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="7"
                                    class="px-4 py-8 text-center text-gray-400"
                                >
                                    Chưa có đơn hàng nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($orders->hasPages())
            <div>{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
