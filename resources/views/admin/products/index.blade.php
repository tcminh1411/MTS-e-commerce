{{-- Mẫu giao diện danh sách sản phẩm trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Sản phẩm')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách sản phẩm
                <span class="text-gray-500">({{ $products->total() }})</span>
            </h2>
            <a
                href="{{ route('admin.products.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Thêm sản phẩm
            </a>
        </div>

        <form
            method="GET"
            action="{{ route('admin.products.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow"
        >
            <div class="min-w-[220px] flex-1">
                <label
                    for="product-search"
                    class="mb-1 block text-xs font-medium text-gray-600"
                    >Tìm kiếm</label
                >
                <input
                    id="product-search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tên hoặc SKU..."
                    class="w-full rounded border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600"
                    >Danh mục</label
                >
                <select
                    name="category_id"
                    class="rounded border-gray-300 px-3 py-2 text-sm w-60"
                >
                    <option value="">Tất cả</option>
                    @foreach ($categories as $c)
                        <option
                            value="{{ $c->id }}"
                            @selected (request('category_id')==$c->id)
                        >
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600"
                    >Trạng thái</label
                >
                <select
                    name="status"
                    class="rounded border-gray-300 px-3 py-2 text-sm w-60"
                >
                    <option value="">Tất cả</option>
                    @foreach (['draft' => 'Nháp', 'active' => 'Đang bán', 'inactive' => 'Ẩn'] as $k => $v)
                        <option
                            value="{{ $k }}"
                            @selected (request('status')===$k)
                        >
                            {{ $v }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button
                type="submit"
                class="rounded bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900"
            >
                Lọc
            </button>
            @if (request()->hasAny(['q', 'category_id', 'status']))
                <a
                    href="{{ route('admin.products.index') }}"
                    class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >Xoá lọc</a
                >
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Ảnh</th>
                        <th class="px-4 py-3 text-left">Tên / SKU</th>
                        <th class="px-4 py-3 text-left">Danh mục</th>
                        <th class="px-4 py-3 text-left">Thương hiệu</th>
                        <th class="px-4 py-3 text-right">Giá</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($products as $p)
                        <tr>
                            <td class="px-4 py-3">
                                @if ($p->thumbnail)
                                    <img
                                        src="{{ $p->thumbnail }}"
                                        class="h-12 w-12 object-cover rounded"
                                    />
                                @else
                                    <div
                                        class="h-12 w-12 bg-gray-100 rounded"
                                    ></div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $p->name }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $p->sku }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ $p->category?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $p->brand?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($p->is_on_sale)
                                    <div
                                        class="text-xs line-through text-gray-400"
                                    >
                                        {{ number_format($p->price) }}đ
                                    </div>
                                    <div class="text-red-600 font-semibold">
                                        {{ number_format($p->sale_price) }}đ
                                    </div>
                                @else
                                    <div class="font-semibold">
                                        {{ number_format($p->price) }}đ
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                        $map = ['draft'=>'bg-gray-200 text-gray-700','active'=>'bg-green-100
                        text-green-700','inactive'=>'bg-yellow-100 text-yellow-700'];
                        @endphp
                                <span
                                    class="px-2 py-0.5 rounded text-xs {{ $map[$p->status] }}"
                                    >{{ $p->status }}</span
                                >
                            </td>
                            <td
                                class="px-4 py-3 text-right space-x-2 whitespace-nowrap"
                            >
                                <a
                                    href="{{ route('admin.products.edit', $p) }}"
                                    class="text-indigo-600 hover:underline"
                                    >Sửa</a
                                >
                                <form
                                    method="POST"
                                    action="{{ route('admin.products.destroy', $p) }}"
                                    class="inline"
                                    onsubmit="
                                        return confirm('Xoá sản phẩm này?');
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
                                Không có sản phẩm.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div>{{ $products->links() }}</div>
        @endif
    </div>
@endsection
