{{-- Mẫu giao diện danh sách mã giảm giá trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Mã giảm giá')
@section ('page_title', 'Mã giảm giá')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách mã giảm giá
                <span class="text-gray-500">({{ $counts['all'] }})</span>
            </h2>
            <a
                href="{{ route('admin.coupons.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Thêm mã
            </a>
        </div>

        <form
            method="GET"
            action="{{ route('admin.coupons.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow"
        >
            @if ($status)
                <input type="hidden" name="status" value="{{ $status }}" />
            @endif
            <div class="min-w-[220px] flex-1">
                <label
                    for="coupon-search"
                    class="mb-1 block text-xs font-medium text-gray-600"
                    >Tìm kiếm</label
                >
                <input
                    id="coupon-search"
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Mã hoặc tên..."
                    class="w-full rounded border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
            <button
                type="submit"
                class="rounded bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900"
            >
                Tìm
            </button>
            @if (request('q'))
                <a
                    href="{{ route('admin.coupons.index', $status ? ['status' => $status] : []) }}"
                    class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >Xoá lọc</a
                >
            @endif
        </form>

        {{-- Filter tabs --}}
        <div class="flex flex-wrap items-center gap-2 text-sm">
            @php
        $tabs = [
            ''         => ['all', 'Tất cả'],
            'active'   => ['active', 'Đang chạy'],
            'inactive' => ['inactive', 'Đã tắt'],
            'expired'  => [null, 'Hết hạn'],
            'upcoming' => [null, 'Sắp chạy'],
        ];
    @endphp
            @foreach ($tabs as $key => [$countKey, $label])
                @php $isActive = ($status ?? '') === $key; @endphp
                <a
                    href="{{ route('admin.coupons.index', array_filter(['status' => $key ?: null, 'q' => request('q') ?: null])) }}"
                    class="px-3 py-1.5 rounded-full border {{ $isActive ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                >
                    {{ $label }}
                    @if ($countKey)
                        <span
                            class="ml-1 text-xs {{ $isActive ? 'text-indigo-100' : 'text-gray-500' }}"
                            >({{ $counts[$countKey] }})</span
                        >
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            @if ($coupons->isEmpty())
                <div class="p-8 text-center text-gray-500">
                    Không có mã giảm giá nào.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="bg-gray-50 text-left text-xs uppercase text-gray-500"
                        >
                            <tr>
                                <th class="px-3 py-2 w-12">#</th>
                                <th class="px-3 py-2 w-32">Mã</th>
                                <th class="px-3 py-2">Tên</th>
                                <th class="px-3 py-2 w-40">Giá trị</th>
                                <th class="px-3 py-2 w-32 text-center">
                                    Điều kiện
                                </th>
                                <th class="px-3 py-2 w-28 text-center">
                                    Lượt dùng
                                </th>
                                <th class="px-3 py-2 w-44">Thời gian</th>
                                <th class="px-3 py-2 w-20 text-center">Bật</th>
                                <th class="px-3 py-2 w-40">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y align-top">
                            @foreach ($coupons as $c)
                                @php
                            $now = now();
                            $isExpired = $c->ends_at && $c->ends_at < $now;
                            $isUpcoming = $c->starts_at && $c->starts_at > $now;
                            $isOutOfQuota = $c->usage_limit !== null && $c->used_count >= $c->usage_limit;
                            $isRunning = $c->is_active && !$isExpired && !$isUpcoming && !$isOutOfQuota;
                        @endphp
                                <tr>
                                    <td
                                        class="px-3 py-3 font-mono text-gray-500"
                                    >
                                        {{ $c->id }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <span
                                            class="font-mono font-bold text-indigo-700"
                                            >{{ $c->code }}</span
                                        >
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $c->name }}
                                        </div>
                                        @if ($c->description)
                                            <div
                                                class="text-xs text-gray-500 mt-0.5"
                                            >
                                                {{ \Illuminate\Support\Str::limit($c->description, 60) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        @if ($c->type === 'percent')
                                            <span
                                                class="inline-block px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800"
                                                >%</span
                                            >
                                            <strong>{{ $c->value }}%</strong>
                                            @if ($c->max_discount_amount)
                                                <div
                                                    class="text-xs text-gray-500"
                                                >
                                                    tối đa {{ number_format($c->max_discount_amount, 0, ',', '.') }}đ
                                                </div>
                                            @endif
                                        @else
                                            <span
                                                class="inline-block px-2 py-0.5 rounded text-xs bg-green-100 text-green-800"
                                                >₫</span
                                            >
                                            <strong
                                                >{{ number_format($c->value, 0, ',', '.') }}đ</strong
                                            >
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center text-xs">
                                        @if ($c->min_order_amount > 0)
                                            <div>
                                                Từ {{ number_format($c->min_order_amount, 0, ',', '.') }}đ
                                            </div>
                                        @else
                                            <div class="text-gray-400">
                                                Không
                                            </div>
                                        @endif
                                        @if ($c->usage_limit_per_user)
                                            <div class="text-gray-500">
                                                / {{ $c->usage_limit_per_user }} lượt/user
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span
                                            class="font-medium text-gray-800"
                                            >{{ $c->used_count }}</span
                                        >
                                        <span class="text-gray-400"
                                            >/ {{ $c->usage_limit ?? '∞' }}</span
                                        >
                                        <div class="text-xs text-gray-500">
                                            {{ $c->usages_count }} giao dịch
                                        </div>
                                    </td>
                                    <td
                                        class="px-3 py-3 text-xs text-gray-600 whitespace-nowrap"
                                    >
                                        <div>
                                            BĐ: {{ $c->starts_at?->format('d/m/Y H:i') ?? '—' }}
                                        </div>
                                        <div>
                                            KT: {{ $c->ends_at?->format('d/m/Y H:i') ?? '—' }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if ($isRunning)
                                            <span
                                                class="inline-block px-2 py-0.5 rounded text-xs bg-green-100 text-green-800"
                                                >Chạy</span
                                            >
                                        @elseif ($isExpired)
                                            <span
                                                class="inline-block px-2 py-0.5 rounded text-xs bg-red-100 text-red-800"
                                                >Hết hạn</span
                                            >
                                        @elseif ($isUpcoming)
                                            <span
                                                class="inline-block px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800"
                                                >Sắp chạy</span
                                            >
                                        @elseif ($isOutOfQuota)
                                            <span
                                                class="inline-block px-2 py-0.5 rounded text-xs bg-orange-100 text-orange-800"
                                                >Hết lượt</span
                                            >
                                        @else
                                            <span
                                                class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600"
                                                >Tắt</span
                                            >
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <div
                                            class="flex items-center gap-2 text-xs"
                                        >
                                            <a
                                                href="{{ route('admin.coupons.edit', $c) }}"
                                                class="text-indigo-600 hover:underline"
                                                >Sửa</a
                                            >
                                            <span class="text-gray-300">|</span>
                                            <a
                                                href="{{ route('admin.coupons.usages', $c) }}"
                                                class="text-gray-600 hover:underline"
                                                >Lượt dùng</a
                                            >
                                            <span class="text-gray-300">|</span>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.coupons.destroy', $c) }}"
                                                onsubmit="return confirm('Xoá mã {{ $c->code }}?');"
                                            >
                                                @csrf
                                                @method ('DELETE')
                                                <button
                                                    type="submit"
                                                    class="text-red-600 hover:underline"
                                                >
                                                    Xoá
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t">{{ $coupons->links() }}</div>
            @endif
        </div>
    </div>
@endsection
