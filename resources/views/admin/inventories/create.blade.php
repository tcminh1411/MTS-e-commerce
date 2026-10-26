{{-- Mẫu giao diện tạo mới tồn kho trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Nhập / Xuất kho')
@section ('page_title', 'Nhập / Xuất kho')

@section ('content')
    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        <p class="text-sm text-gray-600 mb-5">Chọn kho + sản phẩm + loại thao tác. Sau khi lưu, tồn kho của variant và lịch sử <span class="font-mono">stock_movements</span> sẽ được cập nhật tự động.</p>

        <form method="POST" action="{{ route('admin.inventories.store') }}">
            @csrf

            <div class="space-y-4">
                <div>
                    <label
                        class="block text-sm font-medium mb-1"
                        for="warehouse_id"
                    >
                        Kho <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="warehouse_id"
                        id="warehouse_id"
                        required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">— Chọn kho —</option>
                        @foreach ($warehouses as $w)
                            <option
                                value="{{ $w->id }}"
                                @selected (old('warehouse_id', $selectedWarehouseId) == $w->id)
                            >
                                {{ $w->code }} — {{ $w->name }}
                            </option>
                        @endforeach
                    </select>
                    @error ('warehouse_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label
                        class="block text-sm font-medium mb-1"
                        for="product_variant_id"
                    >
                        Sản phẩm (variant) <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="product_variant_id"
                        id="product_variant_id"
                        required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">— Chọn sản phẩm —</option>
                        @foreach ($variants as $v)
                            <option
                                value="{{ $v->id }}"
                                @selected (old('product_variant_id') == $v->id)
                            >
                                [{{ $v->sku }}] {{ $v->product?->name ?? 'N/A' }}
                                @if ($v->is_default) (Mặc định) @endif
                            </option>
                        @endforeach
                    </select>
                    @error ('product_variant_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">
                        Loại thao tác <span class="text-red-500">*</span>
                    </label>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2">
                            <input
                                type="radio"
                                name="type"
                                value="in"
                                required
                                {{ old('type', 'in') === 'in' ? 'checked' : '' }}
                                class="text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm">Nhập kho (cộng thêm)</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input
                                type="radio"
                                name="type"
                                value="out"
                                {{ old('type') === 'out' ? 'checked' : '' }}
                                class="text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm">Xuất kho (trừ bớt)</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input
                                type="radio"
                                name="type"
                                value="adjust"
                                {{ old('type') === 'adjust' ? 'checked' : '' }}
                                class="text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm"
                                >Điều chỉnh (đặt lại số mới)</span
                            >
                        </label>
                    </div>
                    @error ('type')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label
                        class="block text-sm font-medium mb-1"
                        for="quantity"
                    >
                        Số lượng <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        min="0"
                        required
                        value="{{ old('quantity', 0) }}"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p class="text-xs text-gray-500 mt-1">Với "Điều chỉnh" — đây là số tồn <strong>mới</strong> muốn đặt, không phải delta.</p>
                    @error ('quantity')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="note"
                        >Ghi chú</label
                    >
                    <textarea
                        name="note"
                        id="note"
                        rows="3"
                        maxlength="500"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >{{ old('note') }}</textarea
                    >
                    @error ('note')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button
                    type="submit"
                    class="px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700"
                >
                    Lưu
                </button>
                <a
                    href="{{ route('admin.inventories.index') }}"
                    class="px-4 py-2 rounded border text-sm hover:bg-gray-50"
                    >Huỷ</a
                >
            </div>
        </form>
    </div>
@endsection
