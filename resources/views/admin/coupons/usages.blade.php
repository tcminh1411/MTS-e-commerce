{{-- Lịch sử vẫn hiển thị nếu tài khoản hoặc đơn hàng liên kết không còn. --}}
@extends ('layouts.admin')

@section ('title', 'Lượt dùng mã ' . $coupon->code)
@section ('page_title', 'Lượt dùng mã ' . $coupon->code)

@section ('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">
                Lượt dùng mã
                <span
                    class="font-mono text-indigo-700"
                    >{{ $coupon->code }}</span
                >
            </h1>
            <p class="text-sm text-gray-500">
                {{ $coupon->name }} — tổng {{ $coupon->usages()->count() }} lượt
            </p>
        </div>
        <a
            href="{{ route('admin.coupons.edit', $coupon) }}"
            class="text-sm text-indigo-600 hover:underline"
            >← Sửa mã</a
        >
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        @if ($usages->isEmpty())
            <div class="p-8 text-center text-gray-500">
                Chưa có lượt dùng nào.
            </div>
        @else
            <table class="w-full text-sm">
                <thead
                    class="bg-gray-50 text-left text-xs uppercase text-gray-500"
                >
                    <tr>
                        <th class="px-4 py-2 w-12">#</th>
                        <th class="px-4 py-2">Người dùng</th>
                        <th class="px-4 py-2 w-40">Mã đơn</th>
                        <th class="px-4 py-2 w-32 text-right">Tổng đơn</th>
                        <th class="px-4 py-2 w-32">Trạng thái</th>
                        <th class="px-4 py-2 w-40">Ngày dùng</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($usages as $u)
                        <tr>
                            <td class="px-4 py-3 font-mono text-gray-500">
                                {{ $u->id }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ $u->user?->full_name ?? 'Guest' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $u->user?->email ?? '—' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($u->order)
                                    <a
                                        href="{{ route('admin.orders.show', $u->order) }}"
                                        class="font-mono text-indigo-600 hover:underline"
                                    >
                                        {{ $u->order->order_number }}
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{ $u->order ? number_format($u->order->total, 0, ',', '.') . 'đ' : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($u->order)
                                    <span
                                        class="text-xs"
                                        >{{ $u->order->status }}</span
                                    >
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td
                                class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap"
                            >
                                {{ $u->used_at?->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4 border-t">{{ $usages->links() }}</div>
        @endif
    </div>
@endsection
