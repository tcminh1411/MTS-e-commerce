{{-- Mẫu giao diện danh sách kho hàng trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Kho hàng')
@section ('page_title', 'Kho hàng')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách kho hàng
                <span class="text-gray-500">({{ $warehouses->total() }})</span>
            </h2>
            <a
                href="{{ route('admin.warehouses.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Thêm kho
            </a>
        </div>

        <form
            method="GET"
            action="{{ route('admin.warehouses.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow"
        >
            <div class="min-w-[220px] flex-1">
                <label
                    for="warehouse-search"
                    class="mb-1 block text-xs font-medium text-gray-600"
                    >Tìm kiếm</label
                >
                <input
                    id="warehouse-search"
                    type="text"
                    name="q"
                    value="{{ $keyword }}"
                    placeholder="Tên hoặc mã kho..."
                    class="w-full rounded border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
            <button
                type="submit"
                class="rounded bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900"
            >
                Tìm
            </button>
            @if ($keyword !== '')
                <a
                    href="{{ route('admin.warehouses.index') }}"
                    class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >Xoá lọc</a
                >
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Mã</th>
                        <th class="px-4 py-3 text-left">Tên kho</th>
                        <th class="px-4 py-3 text-left">Điện thoại</th>
                        <th class="px-4 py-3 text-left">Địa chỉ</th>
                        <th class="px-4 py-3 text-center">Số SP</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($warehouses as $w)
                        <tr>
                            <td class="px-4 py-3 font-mono text-gray-700">
                                {{ $w->code }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ $w->name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $w->phone ?: '—' }}
                            </td>
                            <td
                                class="px-4 py-3 text-gray-600 max-w-xs truncate"
                                title="{{ $w->address }}"
                            >
                                {{ $w->address ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ $w->inventories_count }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="px-2 py-0.5 rounded text-xs {{ $w->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}"
                                >
                                    {{ $w->is_active ? 'Hoạt động' : 'Tạm dừng' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a
                                    href="{{ route('admin.warehouses.edit', $w) }}"
                                    class="text-indigo-600 hover:underline text-sm"
                                    >Sửa</a
                                >
                                <form
                                    method="POST"
                                    action="{{ route('admin.warehouses.destroy', $w) }}"
                                    class="inline-block ml-3"
                                    onsubmit="return confirm('Xoá kho này?');"
                                >
                                    @csrf
                                    @method ('DELETE')
                                    <button
                                        type="submit"
                                        class="text-red-600 hover:underline text-sm"
                                    >
                                        Xoá
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="px-4 py-6 text-center text-gray-500"
                            >
                                Chưa có kho nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($warehouses->hasPages())
            <div>{{ $warehouses->links() }}</div>
        @endif
    </div>
@endsection
