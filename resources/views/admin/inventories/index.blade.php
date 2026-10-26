{{-- Mẫu giao diện danh sách tồn kho trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Tồn kho')
@section ('page_title', 'Tồn kho')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách tồn kho
                <span class="text-gray-500">({{ $inventories->total() }})</span>
            </h2>
            <a
                href="{{ route('admin.inventories.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Nhập / Xuất kho
            </a>
        </div>

        <form
            method="GET"
            action="{{ route('admin.inventories.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow"
        >
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600"
                    >Kho hàng</label
                >
                <select
                    name="warehouse_id"
                    class="rounded border-gray-300 text-sm"
                >
                    <option value="">Tất cả kho</option>
                    @foreach ($warehouses as $w)
                        <option
                            value="{{ $w->id }}"
                            @selected ((string) $warehouseId === (string) $w->id)
                        >
                            {{ $w->code }} — {{ $w->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[220px] flex-1">
                <label
                    for="inventory-search"
                    class="mb-1 block text-xs font-medium text-gray-600"
                    >Tìm kiếm</label
                >
                <input
                    id="inventory-search"
                    type="text"
                    name="q"
                    value="{{ $keyword }}"
                    placeholder="SKU hoặc tên sản phẩm..."
                    class="w-full rounded border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
            <button
                type="submit"
                class="rounded bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900"
            >
                Lọc
            </button>
            @if ($keyword !== '' || $warehouseId)
                <a
                    href="{{ route('admin.inventories.index') }}"
                    class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >Xoá lọc</a
                >
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Kho</th>
                        <th class="px-4 py-3 text-left">SKU</th>
                        <th class="px-4 py-3 text-left">Sản phẩm</th>
                        <th class="px-4 py-3 text-center">Tồn</th>
                        <th class="px-4 py-3 text-center">Ngưỡng</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($inventories as $inv)
                        <tr>
                            <td class="px-4 py-3">
                                <span
                                    class="font-mono text-gray-700"
                                    >{{ $inv->warehouse?->code ?? '—' }}</span
                                >
                                <div class="text-xs text-gray-500">
                                    {{ $inv->warehouse?->name }}
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-700">
                                {{ $inv->variant?->sku ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $inv->variant?->product?->name ?? '—' }}
                                @if ($inv->variant?->is_default)
                                    <span class="ml-1 text-xs text-gray-500"
                                        >(Mặc định)</span
                                    >
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-semibold">
                                {{ $inv->quantity }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500">
                                {{ $inv->low_stock_threshold }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($inv->quantity <= 0)
                                    <span
                                        class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700"
                                        >Hết hàng</span
                                    >
                                @elseif ($inv->is_low_stock)
                                    <span
                                        class="px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700"
                                        >Sắp hết</span
                                    >
                                @else
                                    <span
                                        class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700"
                                        >Còn hàng</span
                                    >
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="px-4 py-6 text-center text-gray-500"
                            >
                                Chưa có tồn kho nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($inventories->hasPages())
            <div>{{ $inventories->links() }}</div>
        @endif
    </div>
@endsection
