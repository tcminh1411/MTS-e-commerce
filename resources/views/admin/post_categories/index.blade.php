{{-- Mẫu giao diện danh sách danh mục bài viết trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Danh mục blog')
@section ('page_title', 'Quản lý danh mục blog')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách danh mục blog
                <span class="text-gray-500">({{ $categories->total() }})</span>
            </h2>
            <a
                href="{{ route('admin.post_categories.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Thêm danh mục
            </a>
        </div>

        <form
            method="GET"
            class="bg-white rounded-lg shadow p-4 flex flex-wrap items-end gap-3"
        >
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-medium text-gray-600 mb-1"
                    >Tìm kiếm</label
                >
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tên hoặc slug..."
                    class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1"
                    >Trạng thái</label
                >
                <select
                    name="status"
                    class="rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                    <option value="">Tất cả</option>
                    <option
                        value="active"
                        {{ request('status') === 'active' ? 'selected' : '' }}
                    >
                        Đang hiển thị
                    </option>
                    <option
                        value="inactive"
                        {{ request('status') === 'inactive' ? 'selected' : '' }}
                    >
                        Đang ẩn
                    </option>
                </select>
            </div>
            <button
                type="submit"
                class="px-4 py-2 rounded bg-gray-800 text-white text-sm hover:bg-gray-900"
            >
                Lọc
            </button>
            @if (request()->hasAny(['q', 'status']))
                <a
                    href="{{ route('admin.post_categories.index') }}"
                    class="px-4 py-2 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-50"
                >
                    Xoá lọc
                </a>
            @endif
        </form>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Tên</th>
                        <th class="px-4 py-3 text-left">Slug</th>
                        <th class="px-4 py-3 text-center">Số bài</th>
                        <th class="px-4 py-3 text-center">Thứ tự</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">
                                #{{ $category->id }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $category->name }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-gray-600"
                            >
                                {{ $category->slug }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                {{ $category->posts_count }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                {{ $category->sort_order }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($category->is_active)
                                    <span
                                        class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs"
                                        >Hiển thị</span
                                    >
                                @else
                                    <span
                                        class="inline-block px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 text-xs"
                                        >Đang ẩn</span
                                    >
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a
                                        href="{{ route('admin.post_categories.edit', $category) }}"
                                        class="text-xs text-indigo-600 hover:underline"
                                        >Sửa</a
                                    >
                                    <form
                                        method="POST"
                                        action="{{ route('admin.post_categories.destroy', $category) }}"
                                        onsubmit="return confirm('Xoá danh mục &quot;{{ $category->name }}&quot;? Các bài viết sẽ chuyển sang &quot;Chưa phân loại&quot;.');"
                                        class="inline"
                                    >
                                        @csrf
                                        @method ('DELETE')
                                        <button
                                            type="submit"
                                            class="text-xs text-red-600 hover:underline"
                                        >
                                            Xoá
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-gray-500 text-sm"
                            >
                                Chưa có danh mục nào.
                                <a
                                    href="{{ route('admin.post_categories.create') }}"
                                    class="text-indigo-600 hover:underline"
                                    >Tạo danh mục đầu tiên</a
                                >
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
