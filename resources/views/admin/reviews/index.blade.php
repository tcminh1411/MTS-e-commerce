{{-- Mẫu giao diện danh sách đánh giá trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Đánh giá')
@section ('page_title', 'Đánh giá sản phẩm')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách đánh giá sản phẩm
                <span class="text-gray-500">({{ $counts['all'] }})</span>
            </h2>
        </div>

        <form
            method="GET"
            action="{{ route('admin.reviews.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow"
        >
            <div class="min-w-[220px] flex-1">
                <label
                    for="review-search"
                    class="mb-1 block text-xs font-medium text-gray-600"
                    >Tìm kiếm</label
                >
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Tìm theo sản phẩm / user..."
                    id="review-search"
                    class="w-full rounded border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
            @if ($visibility)
                <input type="hidden" name="visibility" value="{{ $visibility }}" />
            @endif
            <button
                type="submit"
                class="rounded bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900"
            >
                Tìm
            </button>
            @if ($search)
                <a
                    href="{{ route('admin.reviews.index', $visibility ? ['visibility' => $visibility] : []) }}"
                    class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >Xoá lọc</a
                >
            @endif
        </form>

        {{-- Filter tabs --}}
        <div class="flex flex-wrap gap-2 text-sm">
            @php
        $tabs = [
            ''        => ['all', 'Tất cả'],
            'visible' => ['visible', 'Đang hiển thị'],
            'hidden'  => ['hidden', 'Đã ẩn'],
        ];
    @endphp
            @foreach ($tabs as $key => [$countKey, $label])
                @php $active = ($visibility ?? '') === $key; @endphp
                <a
                    href="{{ route('admin.reviews.index', array_filter(['visibility' => $key ?: null, 'q' => $search ?: null])) }}"
                    class="px-3 py-1.5 rounded-full border
                  {{ $active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                >
                    {{ $label }}
                    <span
                        class="ml-1 text-xs {{ $active ? 'text-indigo-100' : 'text-gray-500' }}"
                    >
                        ({{ $counts[$countKey] }})
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            @if ($reviews->isEmpty())
                <div class="p-8 text-center text-gray-500">
                    Không có đánh giá nào.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-[960px] w-full table-fixed text-sm">
                        <colgroup>
                            <col class="w-[5%]" />
                            <col class="w-[19%]" />
                            <col class="w-[17%]" />
                            <col class="w-[8%]" />
                            <col class="w-[23%]" />
                            <col class="w-[13%]" />
                            <col class="w-[15%]" />
                        </colgroup>
                        <thead
                            class="bg-gray-50 text-left text-xs uppercase text-gray-500"
                        >
                            <tr>
                                <th class="px-3 py-2">#</th>
                                <th class="px-3 py-2">Sản phẩm</th>
                                <th class="px-3 py-2">Người viết</th>
                                <th class="px-3 py-2">Sao</th>
                                <th class="px-3 py-2">Nội dung</th>
                                <th class="px-3 py-2">Hiển thị</th>
                                <th class="px-3 py-2">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y align-top">
                            @foreach ($reviews as $rv)
                                <tr>
                                    <td
                                        class="px-3 py-3 font-mono text-gray-500"
                                    >
                                        {{ $rv->id }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $rv->product?->name ?? '(đã xoá)' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Đơn:
                                            <span
                                                class="font-mono"
                                                >{{ $rv->order?->order_number }}</span
                                            >
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="font-medium">
                                            {{ $rv->user?->full_name ?? '(ẩn danh)' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $rv->user?->email }}
                                        </div>
                                    </td>
                                    <td
                                        class="px-3 py-3 text-yellow-500 whitespace-nowrap"
                                    >
                                        {{ $rv->stars }}
                                        <span class="text-gray-500 text-xs"
                                            >({{ $rv->rating }}/5)</span
                                        >
                                    </td>
                                    <td class="px-3 py-3 text-gray-700 break-words">
                                        {{ \Illuminate\Support\Str::limit($rv->comment, 120) ?: '—' }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <span
                                            class="inline-block rounded px-2 py-0.5 text-xs font-medium {{ $rv->is_visible ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}"
                                        >
                                            {{ $rv->is_visible ? 'Đang hiển thị' : 'Đã ẩn' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <form
                                            method="POST"
                                            action="{{ route($rv->is_visible ? 'admin.reviews.hide' : 'admin.reviews.show', $rv) }}"
                                        >
                                            @csrf
                                            @method ('PATCH')
                                            <button
                                                class="rounded px-3 py-1 text-xs font-medium whitespace-nowrap {{ $rv->is_visible ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }}"
                                            >
                                                {{ $rv->is_visible ? 'Ẩn đánh giá' : 'Hiện đánh giá' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t">{{ $reviews->links() }}</div>
            @endif
        </div>
    </div>
@endsection
