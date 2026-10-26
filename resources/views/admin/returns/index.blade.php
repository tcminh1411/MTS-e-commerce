{{-- Danh sách yêu cầu trả hàng trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Trả hàng / Hoàn tiền')
@section ('title', 'Trả hàng / Hoàn tiền')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách trả hàng / hoàn tiền
                <span class="text-gray-500">({{ $returns->total() }})</span>
            </h2>
        </div>

        {{-- FILTER --}}
        <div class="rounded-lg bg-white p-4 shadow">
            <form
                method="GET"
                action="{{ route('admin.orders.returns.index') }}"
                class="grid grid-cols-1 gap-3 items-end md:grid-cols-12"
            >
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1"
                        >Trạng thái</label
                    >
                    <select
                        name="status"
                        class="w-full rounded border px-3 py-2 text-sm"
                    >
                        <option value="">Tất cả</option>
                        @foreach ($statuses as $key => $label)
                            <option
                                value="{{ $key }}"
                                @selected (request('status')===$key)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-6">
                    <label class="block text-xs font-medium text-gray-600 mb-1"
                        >Tìm theo mã đơn</label
                    >
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="VD: ORD-20261009-0001"
                        class="w-full rounded border px-3 py-2 text-sm"
                    />
                </div>

                <div class="flex justify-end gap-2 md:col-span-3">
                    <button
                        type="submit"
                        class="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                    >
                        Lọc
                    </button>
                    @if (request()->hasAny(['status', 'search']))
                        <a
                            href="{{ route('admin.orders.returns.index') }}"
                            class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Xoá lọc
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Mã đơn</th>
                        <th class="px-4 py-3">Khách hàng</th>
                        <th class="px-4 py-3 text-right">Tổng đơn</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Ngày yêu cầu</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($returns as $r)
                        @php
                $st = [
                'pending' => 'bg-yellow-100 text-yellow-800',
                'approved' => 'bg-blue-100 text-blue-800',
                'rejected' => 'bg-red-100 text-red-800',
                'refunded' => 'bg-green-100 text-green-800',
                ][$r->status] ?? 'bg-gray-100 text-gray-700';
                @endphp
                        <tr class="border-b last:border-0 hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $r->id }}</td>
                            <td class="px-4 py-3 font-mono">
                                {{ $r->order?->order_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ $r->user?->full_name ?? 'Guest' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $r->user?->email ?? '—' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{ number_format($r->order?->total ?? 0, 0, ',', '.') }} ₫
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-0.5 rounded text-xs {{ $st }}"
                                >
                                    {{ $statuses[$r->status] ?? $r->status }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap"
                            >
                                {{ $r->requested_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a
                                    href="{{ route('admin.orders.returns.show', $r) }}"
                                    class="text-indigo-600 hover:underline"
                                    >Xem →</a
                                >
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-gray-500"
                            >
                                Chưa có yêu cầu trả hàng nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($returns->hasPages())
                <div class="p-3 border-t">{{ $returns->links() }}</div>
            @endif
        </div>
    </div>
@endsection
