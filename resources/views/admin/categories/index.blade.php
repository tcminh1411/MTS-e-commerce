{{-- Mẫu giao diện danh sách danh mục trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Danh mục')
@section ('title', 'Danh mục')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách danh mục
                <span class="text-gray-500">({{ $categories->total() }})</span>
            </h2>
            <a
                href="{{ route('admin.categories.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Thêm danh mục
            </a>
        </div>

        <form
            method="GET"
            action="{{ route('admin.categories.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow"
        >
            <div class="min-w-[220px] flex-1">
                <label
                    for="category-search"
                    class="mb-1 block text-xs font-medium text-gray-600"
                    >Tìm kiếm</label
                >
                <input
                    id="category-search"
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
                    href="{{ route('admin.categories.index') }}"
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
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Tên</th>
                        <th class="px-4 py-3 text-left">Danh mục cha</th>
                        <th class="px-4 py-3 text-center">Sản phẩm</th>
                        <th class="px-4 py-3 text-center">Thứ tự</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($categories as $c)
                        <tr>
                            <td class="px-4 py-3">{{ $c->id }}</td>
                            <td class="px-4 py-3 font-medium">
                                {{ $c->name }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $c->parent?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ $c->products_count }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ $c->sort_order }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="px-2 py-0.5 rounded text-xs {{ $c->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}"
                                >
                                    {{ $c->is_active ? 'Hoạt động' : 'Ẩn' }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-right space-x-2 whitespace-nowrap"
                            >
                                <a
                                    href="{{ route('admin.categories.edit', $c) }}"
                                    class="text-indigo-600 hover:underline"
                                    >Sửa</a
                                >
                                <form
                                    method="POST"
                                    action="{{ route('admin.categories.destroy', $c) }}"
                                    class="inline"
                                    onsubmit="
                                        return confirm('Xoá danh mục này?');
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
                                colspan="7"
                                class="px-4 py-8 text-center text-gray-400"
                            >
                                Chưa có danh mục.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div>{{ $categories->links() }}</div>
        @endif
    </div>
@endsection
