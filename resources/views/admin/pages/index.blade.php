{{-- Mẫu giao diện danh sách trang tĩnh trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Trang tĩnh')
@section ('page_title', 'Quản lý trang tĩnh')

@section ('content')
    <div class="space-y-4">
        {{-- Header + nút thêm --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">
                    Danh sách trang tĩnh
                    <span class="text-gray-500">({{ $pages->total() }})</span>
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Trang About / Terms / Privacy và các trang nội dung khác.</p>
            </div>
            <a
                href="{{ route('admin.pages.create') }}"
                class="px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700"
            >
                + Thêm trang
            </a>
        </div>

        {{-- Filter --}}
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
                    placeholder="Tiêu đề hoặc slug..."
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
                    href="{{ route('admin.pages.index') }}"
                    class="px-4 py-2 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-50"
                >
                    Xoá lọc
                </a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Tiêu đề</th>
                        <th class="px-4 py-3 text-left">Slug</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3 text-center">Footer</th>
                        <th class="px-4 py-3 text-left">Cập nhật</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pages as $page)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">
                                #{{ $page->id }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $page->title }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-gray-600"
                            >
                                /page/{{ $page->slug }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($page->is_active)
                                    <span
                                        class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs"
                                    >
                                        Hiển thị
                                    </span>
                                @else
                                    <span
                                        class="inline-block px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 text-xs"
                                    >
                                        Đang ẩn
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($page->show_in_footer)
                                    <span
                                        class="inline-block px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs"
                                    >
                                        Footer #{{ $page->footer_sort }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $page->updated_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a
                                        href="{{ route('page.show', $page) }}"
                                        target="_blank"
                                        class="text-xs text-gray-600 hover:underline"
                                    >
                                        Xem
                                    </a>
                                    <a
                                        href="{{ route('admin.pages.edit', $page) }}"
                                        class="text-xs text-indigo-600 hover:underline"
                                    >
                                        Sửa
                                    </a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.pages.destroy', $page) }}"
                                        onsubmit="return confirm('Xoá trang &quot;{{ $page->title }}&quot;?');"
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
                                Chưa có trang nào.
                                <a
                                    href="{{ route('admin.pages.create') }}"
                                    class="text-indigo-600 hover:underline"
                                >
                                    Tạo trang đầu tiên
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($pages->hasPages())
            <div>{{ $pages->links() }}</div>
        @endif
    </div>
@endsection
