{{-- Mẫu giao diện danh sách thương hiệu trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Thương hiệu')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách thương hiệu
                <span class="text-gray-500">({{ $brands->total() }})</span>
            </h2>
            <a
                href="{{ route('admin.brands.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Thêm thương hiệu
            </a>
        </div>

        <form
            method="GET"
            action="{{ route('admin.brands.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow"
        >
            <div class="min-w-[220px] flex-1">
                <label
                    for="brand-search"
                    class="mb-1 block text-xs font-medium text-gray-600"
                    >Tìm kiếm</label
                >
                <input
                    id="brand-search"
                    type="text"
                    name="q"
                    value="{{ $keyword }}"
                    placeholder="Tên hoặc slug..."
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
                    href="{{ route('admin.brands.index') }}"
                    class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                    Xoá lọc
                </a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Logo</th>
                        <th class="px-4 py-3 text-left">Tên</th>
                        <th class="px-4 py-3 text-left">Slug</th>
                        <th class="px-4 py-3 text-center">Sản phẩm</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($brands as $b)
                        <tr>
                            <td class="px-4 py-3">
                                @if ($b->logo_url)
                                    @if ($b->logo_url_full)
                                        <img
                                            src="{{ $b->logo_url_full }}"
                                            class="h-10 rounded object-contain"
                                        />
                                    @else
                                        <span class="text-xs text-gray-400"
                                            >—</span
                                        >
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ $b->name }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $b->slug }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ $b->products_count }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="px-2 py-0.5 rounded text-xs {{ $b->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}"
                                >
                                    {{ $b->is_active ? 'Hoạt động' : 'Ẩn' }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-right space-x-2 whitespace-nowrap"
                            >
                                <a
                                    href="{{ route('admin.brands.edit', $b) }}"
                                    class="text-indigo-600 hover:underline"
                                    >Sửa</a
                                >
                                <form
                                    method="POST"
                                    action="{{ route('admin.brands.destroy', $b) }}"
                                    class="inline"
                                    onsubmit="
                                        return confirm('Xoá thương hiệu này?');
                                    "
                                >
                                    @csrf
                                    @method ('DELETE')
                                    <button
                                        class="text-red-600 hover:underline"
                                    >
                                        Xoá
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-gray-400"
                            >
                                Chưa có thương hiệu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($brands->hasPages())
            <div>{{ $brands->links() }}</div>
        @endif
    </div>
@endsection
