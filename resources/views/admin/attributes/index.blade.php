{{-- Danh sách thuộc tính sản phẩm trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Thuộc tính sản phẩm')

@section ('content')
    <div class="space-y-4">
        @if (session('error'))
            <div class="rounded bg-red-100 p-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded bg-red-100 p-3 text-sm text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách thuộc tính
                <span class="text-gray-500">({{ $attributes->total() }})</span>
            </h2>
            <a
                href="{{ route('admin.attributes.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Thêm thuộc tính
            </a>
        </div>

        <form
            method="GET"
            action="{{ route('admin.attributes.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow"
        >
            <div class="min-w-[220px] flex-1">
                <label for="attribute-search" class="mb-1 block text-xs font-medium text-gray-600">
                    Tìm kiếm
                </label>
                <input
                    id="attribute-search"
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
                    href="{{ route('admin.attributes.index') }}"
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
                        <th class="px-4 py-3 text-left">Slug</th>
                        <th class="px-4 py-3 text-center">Kiểu</th>
                        <th class="px-4 py-3 text-center">Giá trị</th>
                        <th class="px-4 py-3 text-center">Sản phẩm</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($attributes as $attribute)
                        <tr>
                            <td class="px-4 py-3">{{ $attribute->id }}</td>
                            <td class="px-4 py-3 font-medium">{{ $attribute->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $attribute->slug }}</td>
                            <td class="px-4 py-3 text-center">
                                {{ $attribute->type === 'color' ? 'Màu sắc' : 'Danh sách' }}
                            </td>
                            <td class="px-4 py-3 text-center">{{ $attribute->values_count }}</td>
                            <td class="px-4 py-3 text-center">{{ $attribute->product_attributes_count }}</td>
                            <td class="space-x-3 whitespace-nowrap px-4 py-3 text-right">
                                <a
                                    href="{{ route('admin.attributes.edit', $attribute) }}"
                                    class="text-indigo-600 hover:underline"
                                >
                                    Sửa
                                </a>
                                <form
                                    method="POST"
                                    action="{{ route('admin.attributes.destroy', $attribute) }}"
                                    class="inline"
                                    onsubmit="return confirm('Xoá thuộc tính này?')"
                                >
                                    @csrf
                                    @method ('DELETE')
                                    <button class="text-red-600 hover:underline">Xoá</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                Chưa có thuộc tính.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attributes->hasPages())
            <div>{{ $attributes->links() }}</div>
        @endif
    </div>
@endsection
