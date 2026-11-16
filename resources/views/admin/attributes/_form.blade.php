{{-- Form thuộc tính và các giá trị của thuộc tính. --}}
<div class="space-y-5">
    @if ($errors->any())
        <div class="rounded bg-red-100 p-3 text-sm text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <label for="attribute-name" class="mb-1 block text-sm font-medium">Tên thuộc tính *</label>
            <input
                id="attribute-name"
                name="name"
                value="{{ old('name', $attribute->name) }}"
                maxlength="100"
                required
                class="w-full rounded border-gray-300"
            />
            @error ('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="attribute-slug" class="mb-1 block text-sm font-medium">Slug</label>
            <input
                id="attribute-slug"
                name="slug"
                value="{{ old('slug', $attribute->slug) }}"
                maxlength="100"
                placeholder="Để trống để tự tạo"
                class="w-full rounded border-gray-300"
            />
            @error ('slug')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="attribute-type" class="mb-1 block text-sm font-medium">Kiểu thuộc tính</label>
            <select id="attribute-type" name="type" class="w-full rounded border-gray-300">
                <option value="select" @selected(old('type', $attribute->type) === 'select')>Danh sách</option>
                <option value="color" @selected(old('type', $attribute->type) === 'color')>Màu sắc</option>
            </select>
            @error ('type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="attribute-order" class="mb-1 block text-sm font-medium">Thứ tự</label>
            <input
                id="attribute-order"
                name="sort_order"
                type="number"
                min="0"
                value="{{ old('sort_order', $attribute->sort_order ?? 0) }}"
                class="w-full rounded border-gray-300"
            />
            @error ('sort_order')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <section class="space-y-3 border-t pt-5">
        <div>
            <h2 class="font-semibold text-gray-800">Giá trị thuộc tính</h2>
            <p class="text-xs text-gray-500">
                Giá trị đang được dùng bởi biến thể không thể xoá.
            </p>
        </div>

        @foreach ($attribute->values as $value)
            <div class="grid grid-cols-1 items-end gap-3 rounded border p-3 sm:grid-cols-[1fr_140px_auto]">
                <div>
                    <label class="mb-1 block text-xs font-medium">Giá trị</label>
                    <input
                        name="values[{{ $value->id }}][value]"
                        value="{{ old('values.'.$value->id.'.value', $value->value) }}"
                        maxlength="150"
                        class="w-full rounded border-gray-300 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Mã màu</label>
                    <input
                        name="values[{{ $value->id }}][color_code]"
                        type="text"
                        value="{{ old('values.'.$value->id.'.color_code', $value->color_code) }}"
                        placeholder="#000000"
                        maxlength="7"
                        class="w-full rounded border-gray-300 text-sm"
                    />
                </div>
                <label class="flex items-center gap-2 pb-2 text-sm text-red-600">
                    <input type="checkbox" name="delete_value_ids[]" value="{{ $value->id }}" />
                    Xoá
                </label>
            </div>
        @endforeach

        <div id="new-attribute-values" class="space-y-3">
            @foreach (old('new_values', [['value' => '', 'color_code' => '']]) as $index => $newValue)
                <div class="grid grid-cols-1 items-end gap-3 rounded border border-dashed p-3 sm:grid-cols-[1fr_140px]">
                    <div>
                        <label class="mb-1 block text-xs font-medium">Giá trị mới</label>
                        <input
                            name="new_values[{{ $index }}][value]"
                            value="{{ $newValue['value'] ?? '' }}"
                            maxlength="150"
                            class="w-full rounded border-gray-300 text-sm"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium">Mã màu</label>
                        <input
                            name="new_values[{{ $index }}][color_code]"
                            type="text"
                            value="{{ $newValue['color_code'] ?? '' }}"
                            placeholder="#000000"
                            maxlength="7"
                            class="w-full rounded border-gray-300 text-sm"
                        />
                    </div>
                </div>
            @endforeach
        </div>
        <button
            type="button"
            id="add-attribute-value"
            class="rounded border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
        >
            + Thêm giá trị
        </button>
    </section>

    <div class="flex gap-3 border-t pt-4">
        <button type="submit" class="rounded bg-indigo-600 px-5 py-2 font-medium text-white hover:bg-indigo-700">
            Lưu
        </button>
        <a href="{{ route('admin.attributes.index') }}" class="rounded bg-gray-200 px-5 py-2 text-gray-800 hover:bg-gray-300">
            Huỷ
        </a>
    </div>
</div>

<script>
    const addAttributeValueButton = document.getElementById('add-attribute-value');
    const newAttributeValues = document.getElementById('new-attribute-values');
    let newAttributeValueIndex = {{ count(old('new_values', [['value' => '', 'color_code' => '']])) }};

    addAttributeValueButton.addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 items-end gap-3 rounded border border-dashed p-3 sm:grid-cols-[1fr_140px]';
        row.innerHTML = `
            <div>
                <label class="mb-1 block text-xs font-medium">Giá trị mới</label>
                <input name="new_values[${newAttributeValueIndex}][value]" maxlength="150" class="w-full rounded border-gray-300 text-sm" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Mã màu</label>
                <input name="new_values[${newAttributeValueIndex}][color_code]" type="text" maxlength="7" placeholder="#000000" class="w-full rounded border-gray-300 text-sm" />
            </div>
        `;
        newAttributeValues.append(row);
        newAttributeValueIndex++;
    });
</script>
