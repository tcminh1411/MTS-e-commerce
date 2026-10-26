{{-- Mẫu giao diện danh sách câu hỏi thường gặp trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'FAQ')
@section ('page_title', 'Câu hỏi thường gặp')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách câu hỏi thường gặp
                <span class="text-gray-500">({{ $counts['all'] }})</span>
            </h2>
            <a
                href="{{ route('admin.faqs.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Thêm FAQ
            </a>
        </div>

        {{-- Filter --}}
        <form
            method="GET"
            action="{{ route('admin.faqs.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow"
        >
            <div class="min-w-[220px] flex-1">
                <label
                    for="faq-search"
                    class="mb-1 block text-xs font-medium text-gray-600"
                    >Tìm kiếm</label
                >
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Tìm câu hỏi / câu trả lời..."
                    id="faq-search"
                    class="w-full rounded border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600"
                    >Danh mục</label
                >
                <select
                    name="category"
                    class="border rounded-lg px-3 py-2 text-sm w-60"
                >
                    <option value="">Tất cả danh mục</option>
                    @foreach (\App\Models\Faq::categories() as $key => $label)
                        <option value="{{ $key }}" @selected ($category===$key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button
                type="submit"
                class="rounded bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900"
            >
                Tìm
            </button>
            @if ($search || $category)
                <a
                    href="{{ route('admin.faqs.index') }}"
                    class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                    Xoá lọc
                </a>
            @endif
        </form>

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
            @if ($faqs->isEmpty())
                <div class="p-8 text-center text-gray-500">
                    Không có FAQ nào.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="bg-gray-50 text-left text-xs uppercase text-gray-500"
                        >
                            <tr>
                                <th class="px-3 py-2 w-12">#</th>
                                <th class="px-3 py-2 w-12 text-center">Sort</th>
                                <th class="px-3 py-2">Câu hỏi</th>
                                <th class="px-3 py-2 w-40">Danh mục</th>
                                <th class="px-3 py-2 w-24 text-center">
                                    Hiển thị
                                </th>
                                <th class="px-3 py-2 w-40">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($faqs as $faq)
                                <tr>
                                    <td
                                        class="px-3 py-3 font-mono text-gray-500"
                                    >
                                        {{ $faq->id }}
                                    </td>
                                    <td
                                        class="px-3 py-3 text-center text-gray-700"
                                    >
                                        {{ $faq->sort_order }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $faq->question }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ \Illuminate\Support\Str::limit($faq->answer, 100) }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span
                                            class="inline-block px-2 py-0.5 rounded text-xs bg-indigo-50 text-indigo-700"
                                        >
                                            {{ $faq->category_label }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if ($faq->is_active)
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
                                                href="{{ route('admin.faqs.edit', $faq) }}"
                                                class="text-indigo-600 hover:underline text-xs"
                                                >Sửa</a
                                            >
                                            <form
                                                method="POST"
                                                action="{{ route('admin.faqs.destroy', $faq) }}"
                                                onsubmit="return confirm('Xoá FAQ #{{ $faq->id }}?');"
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
                <div class="p-4 border-t">{{ $faqs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
