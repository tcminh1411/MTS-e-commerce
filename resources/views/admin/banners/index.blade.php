{{-- Mẫu giao diện danh sách banner trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Banner')
@section ('page_title', 'Quản lý Banner')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách banner
                <span class="text-gray-500">({{ $banners->total() }})</span>
            </h2>
            <a
                href="{{ route('admin.banners.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Thêm banner
            </a>
        </div>

        <form
            method="GET"
            action="{{ route('admin.banners.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow"
        >
            @if ($position)
                <input type="hidden" name="position" value="{{ $position }}" />
            @endif
            <div class="min-w-[220px] flex-1">
                <label
                    for="banner-search"
                    class="mb-1 block text-xs font-medium text-gray-600"
                    >Tìm kiếm</label
                >
                <input
                    id="banner-search"
                    type="text"
                    name="q"
                    value="{{ $keyword }}"
                    placeholder="Tiêu đề hoặc liên kết..."
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
                    href="{{ route('admin.banners.index', $position ? ['position' => $position] : []) }}"
                    class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                    Xoá lọc
                </a>
            @endif
        </form>

        {{-- Filter tabs --}}
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <a
                href="{{ route('admin.banners.index', array_filter(['q' => $keyword ?: null])) }}"
                class="px-3 py-1.5 rounded-full border {{ !$position ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
            >
                Tất cả
                <span class="ml-1 text-xs opacity-75"
                    >({{ $counts['all'] }})</span
                >
            </a>
            @foreach (\App\Models\Banner::POSITIONS as $key => $label)
                @php $active = $position === $key; @endphp
                <a
                    href="{{ route('admin.banners.index', array_filter(['position' => $key, 'q' => $keyword])) }}"
                    class="px-3 py-1.5 rounded-full border {{ $active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="text-sm text-gray-500">
            Hiển thị:
            <span
                class="font-semibold text-green-700"
                >{{ $counts['active'] }}</span
            >
            · Ẩn:
            <span
                class="font-semibold text-gray-700"
                >{{ $counts['inactive'] }}</span
            >
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            @if ($banners->isEmpty())
                <div class="p-8 text-center text-gray-500">
                    Không có banner nào.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="bg-gray-50 text-left text-xs uppercase text-gray-500"
                        >
                            <tr>
                                <th class="px-3 py-2 w-12">#</th>
                                <th class="px-3 py-2 w-40">Ảnh</th>
                                <th class="px-3 py-2">Tiêu đề / Link</th>
                                <th class="px-3 py-2 w-32">Vị trí</th>
                                <th class="px-3 py-2 w-12 text-center">Sort</th>
                                <th class="px-3 py-2 w-24 text-center">
                                    Hiển thị
                                </th>
                                <th class="px-3 py-2 w-40">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($banners as $b)
                                <tr>
                                    <td
                                        class="px-3 py-3 font-mono text-gray-500"
                                    >
                                        {{ $b->id }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <img
                                            src="{{ $b->image_url }}"
                                            alt="{{ $b->title }}"
                                            class="w-32 h-12 object-cover rounded border"
                                        />
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $b->title ?: '(không tiêu đề)' }}
                                        </div>
                                        @if ($b->link)
                                            <div
                                                class="text-xs text-gray-500 mt-1"
                                            >
                                                <span class="text-gray-400"
                                                    >Link:</span
                                                >
                                                <code>{{ $b->link }}</code>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <span
                                            class="inline-block px-2 py-0.5 rounded text-xs bg-indigo-50 text-indigo-700"
                                        >
                                            {{ $b->position_label }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-3 py-3 text-center text-gray-700"
                                    >
                                        {{ $b->sort_order }}
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if ($b->is_active)
                                            <span
                                                class="inline-block px-2 py-0.5 rounded text-xs bg-green-100 text-green-800"
                                                >Hiện</span
                                            >
                                        @else
                                            <span
                                                class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700"
                                                >Ẩn</span
                                            >
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-2">
                                            <a
                                                href="{{ route('admin.banners.edit', $b) }}"
                                                class="text-indigo-600 hover:underline text-xs"
                                                >Sửa</a
                                            >
                                            <form
                                                method="POST"
                                                action="{{ route('admin.banners.destroy', $b) }}"
                                                onsubmit="return confirm('Xoá banner #{{ $b->id }}?');"
                                            >
                                                @csrf
                                                @method ('DELETE')
                                                <button
                                                    type="submit"
                                                    class="text-red-600 hover:underline text-xs"
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
                <div class="p-4 border-t">{{ $banners->links() }}</div>
            @endif
        </div>
    </div>
@endsection
