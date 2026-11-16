{{-- Mẫu giao diện chỉnh sửa sản phẩm trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Sửa sản phẩm')

@section ('content')
    @php
        $attributesData = $attributes->map(fn ($attribute) => [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'values' => $attribute->values
                ->map(fn ($value) => ['id' => $value->id, 'value' => $value->value])
                ->all(),
        ])->all();
        $initialAssignedIds = array_map(
            'intval',
            (array) old('attribute_ids', array_keys($assignedAttributeSettings))
        );
        $initialVariationIds = array_map(
            'intval',
            (array) old(
                'variation_attribute_ids',
                array_keys(array_filter(
                    $assignedAttributeSettings,
                    fn ($settings) => $settings['is_variation']
                ))
            )
        );
        $initialVariantValues = array_map('intval', (array) old('attribute_values', []));
    @endphp
    <div
        x-data="productEditPage(
            @js($attributesData),
            @js($initialAssignedIds),
            @js($initialVariationIds),
            @js($initialVariantValues),
            @js(old('name', '')),
            @js((bool) old('auto_name', true))
        )"
        class="space-y-6"
    >
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
                                    enctype="multipart/form-data"
                                    class="grid grid-cols-1 md:grid-cols-3 gap-3"
                                    data-variant-form
                                >
                                    @csrf
                                    @method ('PUT')
                                    <label class="text-xs font-medium">
                                        Tên biến thể
                                        <input
                                            name="name"
                                            value="{{ $v->name }}"
                                            maxlength="200"
                                            class="mt-1 w-full border rounded px-3 py-2 text-sm"
                                            data-variant-name
                                        />
                                        <span class="mt-1 block font-normal text-gray-500">
                                            Để trống để ghép tên từ thuộc tính.
                                        </span>
                                    </label>
                                    @if ($variationAttributes->isNotEmpty())
                                        @php
                                            $generatedVariantName = $v->attributeValues->pluck('value')->implode(' / ');
                                            $autoNameSelected = old('auto_name', $v->name === $generatedVariantName);
                                        @endphp
                                        <label class="flex items-center gap-2 text-xs">
                                            <input type="hidden" name="auto_name" value="0" />
                                            <input
                                                type="checkbox"
                                                name="auto_name"
                                                value="1"
                                                data-auto-variant-name
                                                @checked((bool) $autoNameSelected)
                                            />
                                            Tự ghép tên từ thuộc tính
                                        </label>
                                    @endif
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
                                    @php
                                        $selectedVariantValues = $v->attributeValues->keyBy('attribute_id');
                                        $oldVariantValueIds = array_map('intval', (array) old(
                                            'attribute_values',
                                            $selectedVariantValues->pluck('id')->all()
                                        ));
                                    @endphp
                                    @foreach ($variationAttributes as $attribute)
                                        <label class="text-xs font-medium">
                                            {{ $attribute->name }}
                                            <select
                                                name="attribute_values[]"
                                                required
                                                class="mt-1 w-full border rounded px-3 py-2 text-sm"
                                                data-variant-value
                                            >
                                                <option value="" disabled @selected(!$selectedVariantValues->has($attribute->id))>
                                                    Chọn {{ strtolower($attribute->name) }}
                                                </option>
                                                @foreach ($attribute->values as $value)
                                                    <option
                                                        value="{{ $value->id }}"
                                                        @selected(in_array($value->id, $oldVariantValueIds, true))
                                                    >
                                                        {{ $value->value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endforeach
                                    <div>
                                        <label class="text-xs font-medium">
                                            Ảnh biến thể
                                            <input
                                                type="file"
                                                name="image_file"
                                                accept=".jpg,.jpeg,.png,.webp"
                                                class="mt-1 w-full border rounded px-3 py-2 text-sm"
                                            />
                                        </label>
                                        @error ('image_file')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium">
                                            Hoặc URL ảnh
                                            <input
                                                type="url"
                                                name="image_url"
                                                value="{{ old('image_url', \Illuminate\Support\Str::startsWith($v->image_url ?? '', ['http://', 'https://']) ? $v->image_url : '') }}"
                                                maxlength="500"
                                                placeholder="https://..."
                                                class="mt-1 w-full border rounded px-3 py-2 text-sm"
                                            />
                                        </label>
                                        @error ('image_url')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    @if ($v->image_url)
                                        <div class="md:col-span-3">
                                            <p class="mb-1 text-xs font-medium">Ảnh hiện tại</p>
                                            <img
                                                src="{{ \Illuminate\Support\Str::startsWith($v->image_url, ['http://', 'https://']) ? $v->image_url : asset('storage/'.$v->image_url) }}"
                                                alt="Ảnh {{ $v->name }}"
                                                class="h-20 w-20 rounded border object-cover"
                                            />
                                        </div>
                                    @endif
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
                enctype="multipart/form-data"
                class="p-4 grid grid-cols-2 md:grid-cols-4 gap-3"
                data-variant-form
            >
                @csrf
                <p
                    x-show="selectedVariationAttributes.length === 0"
                    class="md:col-span-4 rounded border border-dashed p-3 text-sm text-gray-500"
                >
                    Tick thuộc tính và chọn "Dùng để chọn giá trị cho biến thể" ở trên để hiện dropdown.
                    Bạn cũng có thể tự nhập tên biến thể nếu không dùng thuộc tính.
                </p>
                <template x-for="id in assignedIds" :key="'assigned-'+id">
                    <input type="hidden" name="attribute_ids[]" :value="id" />
                </template>
                <template x-for="id in variationIds" :key="'variation-'+id">
                    <input type="hidden" name="variation_attribute_ids[]" :value="id" />
                </template>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium mb-1"
                        >Tên biến thể</label
                    >
                    <input
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Để trống để ghép từ thuộc tính"
                        class="w-full border rounded px-3 py-2 text-sm"
                        data-variant-name
                        x-model="autoName"
                        @input="autoNameEnabled = false"
                    />
                </div>
                <label class="flex items-center gap-2 text-xs">
                    <input type="hidden" name="auto_name" value="0" />
                    <input
                        type="checkbox"
                        name="auto_name"
                        value="1"
                        x-model="autoNameEnabled"
                        @checked(old('auto_name', true))
                    />
                    Tự ghép tên
                </label>
                <template x-for="attribute in selectedVariationAttributes" :key="'value-'+attribute.id">
                    <label class="text-xs font-medium">
                        <span x-text="attribute.name + ' *'"></span>
                        <select
                            name="attribute_values[]"
                            x-model.number="selectedValues[attribute.id]"
                            @change="updateAutoName()"
                            required
                            class="mt-1 w-full border rounded px-3 py-2 text-sm"
                        >
                            <option value="">Chọn</option>
                            <template x-for="value in attribute.values" :key="value.id">
                                <option :value="value.id" x-text="value.value"></option>
                            </template>
                        </select>
                    </label>
                </template>
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
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium mb-1">Ảnh biến thể</label>
                    <input
                        type="file"
                        name="image_file"
                        accept=".jpg,.jpeg,.png,.webp"
                        class="w-full border rounded px-3 py-2 text-sm"
                    />
                    @error ('image_file')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium mb-1">Hoặc URL ảnh</label>
                    <input
                        type="url"
                        name="image_url"
                        value="{{ old('image_url') }}"
                        maxlength="500"
                        placeholder="https://..."
                        class="w-full border rounded px-3 py-2 text-sm"
                    />
                    @error ('image_url')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
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
        function productEditPage(
            allAttributes,
            initialAssignedIds,
            initialVariationIds,
            initialVariantValues,
            initialVariantName,
            initialAutoNameEnabled
        ) {
            return {
                allAttributes,
                assignedIds: initialAssignedIds,
                variationIds: initialVariationIds,
                selectedValues: {},
                autoName: initialVariantName,
                autoNameEnabled: initialAutoNameEnabled,

                get selectedVariationAttributes() {
                    return this.allAttributes.filter((attribute) =>
                        this.assignedIds.includes(attribute.id)
                        && this.variationIds.includes(attribute.id)
                    );
                },

                init() {
                    this.$watch('assignedIds', (assignedIds) => {
                        this.variationIds = this.variationIds.filter((id) => assignedIds.includes(id));
                    });
                    this.$watch('variationIds', () => {
                        this.selectedValues = {};
                        this.updateAutoName();
                    });
                    this.selectedVariationAttributes.forEach((attribute, index) => {
                        if (initialVariantValues[index]) {
                            this.selectedValues[attribute.id] = initialVariantValues[index];
                        }
                    });
                    this.updateAutoName();
                },

                updateAutoName() {
                    if (!this.autoNameEnabled) {
                        return;
                    }

                    this.autoName = this.selectedVariationAttributes
                        .map((attribute) => {
                            const valueId = this.selectedValues[attribute.id];
                            return attribute.values.find((value) => value.id === valueId)?.value;
                        })
                        .filter(Boolean)
                        .join(' / ');
                },
            };
        }

        document.querySelectorAll('[data-variant-form]').forEach((form) => {
            const nameInput = form.querySelector('[data-variant-name]');
            const autoName = form.querySelector('[data-auto-variant-name]');
            const valueSelects = form.querySelectorAll('[data-variant-value]');

            if (!nameInput || !autoName) {
                return;
            }

            const updateName = () => {
                if (!autoName.checked) {
                    return;
                }

                nameInput.value = Array.from(valueSelects)
                    .map((select) => select.selectedOptions[0]?.textContent.trim())
                    .filter(Boolean)
                    .join(' / ');
            };

            valueSelects.forEach((select) => select.addEventListener('change', updateName));
            autoName.addEventListener('change', updateName);
            nameInput.addEventListener('input', () => {
                autoName.checked = false;
            });
            updateName();
        });

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
    </div>
@endsection
