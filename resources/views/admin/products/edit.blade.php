{{-- Mẫu giao diện chỉnh sửa sản phẩm trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Sửa sản phẩm')

@section ('content')
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form
            id="product-edit-form"
            method="POST"
            action="{{ route('admin.products.update', $product) }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method ('PUT')
            @include ('admin.products._form')
        </form>
    </div>
    {{-- VARIANTS --}}
    <div class="bg-white rounded-xl shadow-sm p-6 max-w-5xl mt-6">
        <h2 class="font-semibold mb-4">Biến thể sản phẩm</h2>
        <p class="text-sm text-gray-500 mb-4">
            Biến thể chỉ bị xoá sau khi bấm Lưu. Bấm Huỷ để giữ nguyên.
        </p>

        @if ($product->variants->count())
            <table class="min-w-full text-sm mb-5">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">Tên</th>
                        <th class="px-3 py-2 text-left">SKU</th>
                        <th class="px-3 py-2 text-right">Giá</th>
                        <th class="px-3 py-2 text-center">Mặc định</th>
                        <th class="px-3 py-2 text-center">Trạng thái</th>
                        <th class="px-3 py-2 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($product->variants as $v)
                        <tr data-variant-row>
                            <td class="px-3 py-2">{{ $v->name }}</td>
                            <td class="px-3 py-2 text-gray-500">
                                {{ $v->sku }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ number_format($v->price) }}đ
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if ($v->is_default)
                                    <span class="text-indigo-600">✓</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center text-xs">
                                {{ $v->status }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button
                                    type="button"
                                    data-toggle-variant-edit="variant-edit-{{ $v->id }}"
                                    class="text-indigo-600 hover:underline text-xs"
                                >
                                    Sửa
                                </button>
                                <button
                                    type="button"
                                    data-delete-variant="{{ $v->id }}"
                                    class="ml-3 text-red-600 hover:underline text-xs"
                                >
                                    Xoá
                                </button>
                            </td>
                        </tr>
                        <tr id="variant-edit-{{ $v->id }}" class="hidden bg-gray-50">
                            <td colspan="6" class="p-4">
                                <form
                                    method="POST"
                                    action="{{ route('admin.products.variants.update', [$product, $v]) }}"
                                    class="grid grid-cols-1 md:grid-cols-3 gap-3"
                                >
                                    @csrf
                                    @method ('PUT')
                                    <label class="text-xs font-medium">
                                        Tên biến thể
                                        <input
                                            name="name"
                                            value="{{ $v->name }}"
                                            required
                                            maxlength="200"
                                            class="mt-1 w-full border rounded px-3 py-2 text-sm"
                                        />
                                    </label>
                                    <label class="text-xs font-medium">
                                        SKU
                                        <input
                                            name="sku"
                                            value="{{ $v->sku }}"
                                            maxlength="64"
                                            class="mt-1 w-full border rounded px-3 py-2 text-sm"
                                        />
                                    </label>
                                    <label class="text-xs font-medium">
                                        Giá
                                        <input
                                            name="price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value="{{ $v->price }}"
                                            required
                                            class="mt-1 w-full border rounded px-3 py-2 text-sm"
                                        />
                                    </label>
                                    <label class="text-xs font-medium">
                                        Giá khuyến mãi
                                        <input
                                            name="sale_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value="{{ $v->sale_price }}"
                                            class="mt-1 w-full border rounded px-3 py-2 text-sm"
                                        />
                                    </label>
                                    <label class="text-xs font-medium">
                                        Trạng thái
                                        <select
                                            name="status"
                                            class="mt-1 w-full border rounded px-3 py-2 text-sm"
                                        >
                                            <option value="active" @selected($v->status === 'active')>Hoạt động</option>
                                            <option value="inactive" @selected($v->status === 'inactive')>Ẩn</option>
                                        </select>
                                    </label>
                                    <label class="flex items-center gap-2 self-end pb-2 text-sm">
                                        <input type="hidden" name="is_default" value="0" />
                                        <input
                                            type="checkbox"
                                            name="is_default"
                                            value="1"
                                            @checked($v->is_default)
                                        />
                                        <span>Biến thể mặc định</span>
                                    </label>
                                    <div class="md:col-span-3 flex justify-end gap-2">
                                        <button
                                            type="button"
                                            data-toggle-variant-edit="variant-edit-{{ $v->id }}"
                                            class="px-4 py-2 rounded border text-sm"
                                        >
                                            Huỷ
                                        </button>
                                        <button class="px-4 py-2 rounded bg-indigo-600 text-white text-sm">
                                            Lưu biến thể
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-gray-500 mb-5">Chưa có biến thể nào.</p>
        @endif

        <details class="border rounded-lg">
            <summary
                class="cursor-pointer px-4 py-2 bg-gray-50 font-medium text-sm"
            >
                + Thêm biến thể
            </summary>
            <form
                method="POST"
                action="{{ route('admin.products.variants.store', $product) }}"
                class="p-4 grid grid-cols-2 md:grid-cols-4 gap-3"
            >
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium mb-1"
                        >Tên biến thể *</label
                    >
                    <input
                        name="name"
                        required
                        placeholder="VD: Đỏ / M"
                        class="w-full border rounded px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">SKU</label>
                    <input
                        name="sku"
                        placeholder="Để trống tự sinh"
                        class="w-full border rounded px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1"
                        >Trạng thái *</label
                    >
                    <select
                        name="status"
                        class="w-full border rounded px-3 py-2 text-sm"
                    >
                        <option value="active">Hoạt động</option>
                        <option value="inactive">Ẩn</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Giá *</label>
                    <input
                        name="price"
                        type="number"
                        min="0"
                        required
                        class="w-full border rounded px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Giá KM</label>
                    <input
                        name="sale_price"
                        type="number"
                        min="0"
                        class="w-full border rounded px-3 py-2 text-sm"
                    />
                </div>
                <label class="flex items-center gap-2 self-end pb-2">
                    <input type="hidden" name="is_default" value="0" />
                    <input type="checkbox" name="is_default" value="1" />
                    <span class="text-sm">Mặc định</span>
                </label>
                <div class="self-end">
                    <button
                        class="px-4 py-2 rounded bg-indigo-600 text-white text-sm"
                    >
                        Thêm
                    </button>
                </div>
            </form>
        </details>
    </div>
    <script>
        document.querySelectorAll('[data-toggle-variant-edit]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = document.getElementById(button.dataset.toggleVariantEdit);
                row.classList.toggle('hidden');
            });
        });

        document.querySelectorAll('[data-delete-variant]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!confirm('Đánh dấu xoá biến thể này? Thay đổi chỉ có hiệu lực khi bấm Lưu.')) {
                    return;
                }

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_variant_ids[]';
                input.value = button.dataset.deleteVariant;
                document.getElementById('product-edit-form').append(input);

                button.closest('[data-variant-row]').classList.add('opacity-50');
                button.disabled = true;
                button.textContent = 'Sẽ xóa khi lưu';
            });
        });
    </script>
@endsection
