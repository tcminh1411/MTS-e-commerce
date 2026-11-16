{{-- Mẫu giao diện form sản phẩm trong trang quản trị. --}}
@if ($errors->any())
    <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    {{-- Cột trái: thông tin chính --}}
    <div class="md:col-span-2 space-y-5">
        <div>
            <label class="block text-sm font-medium mb-1">Tên sản phẩm *</label>
            <input
                type="text"
                name="name"
                value="{{ old('name', $product->name) }}"
                class="w-full border rounded px-3 py-2"
                required
            />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Slug</label>
                <input
                    type="text"
                    name="slug"
                    value="{{ old('slug', $product->slug) }}"
                    class="w-full border rounded px-3 py-2"
                />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">SKU</label>
                <input
                    type="text"
                    name="sku"
                    value="{{ old('sku', $product->sku) }}"
                    class="w-full border rounded px-3 py-2"
                />
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Mô tả ngắn</label>
            <textarea
                name="short_description"
                rows="2"
                class="w-full border rounded px-3 py-2"
                >{{ old('short_description', $product->short_description) }}</textarea
            >
        </div>

        <div
            x-data="{ 
        aiLoading: false,
        genDescription() {
            let name = document.querySelector('input[name=name]').value;
            let catSelect = document.querySelector('select[name=category_id]');
            let category = catSelect.options[catSelect.selectedIndex]?.text || 'Sản phẩm';
            
            if(!name) { alert('Vui lòng nhập tên sản phẩm trước!'); return; }

            this.aiLoading = true;
            fetch('{{ route('admin.products.generate-description') }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                },
                body: JSON.stringify({ name: name, category: category })
            })
            .then(res => res.json())
            .then(data => {
                if(data.ok) {
                    document.querySelector('textarea[name=description]').value = data.description;
                } else {
                    alert('Lỗi: ' + data.message);
                }
                this.aiLoading = false;
            })
            .catch(err => { alert('Lỗi mạng!'); this.aiLoading = false; });
        }
    }"
        >
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium">Mô tả chi tiết</label>
                <button
                    type="button"
                    @click="genDescription()"
                    :disabled="aiLoading"
                    class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-semibold rounded hover:bg-indigo-200 transition disabled:opacity-50 flex items-center gap-1"
                >
                    <span x-show="!aiLoading">✨ Viết bằng AI</span>
                    <span x-show="aiLoading">Đang viết... ⏳</span>
                </button>
            </div>
            <textarea
                name="description"
                rows="6"
                class="w-full border rounded px-3 py-2"
                >{{ old('description', $product->description) }}</textarea
            >
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Giá bán *</label>
                <input
                    type="number"
                    name="price"
                    min="0"
                    step="1"
                    value="{{ old('price', $product->price) }}"
                    class="w-full border rounded px-3 py-2"
                    required
                />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1"
                    >Giá khuyến mãi</label
                >
                <input
                    type="number"
                    name="sale_price"
                    min="0"
                    step="1"
                    value="{{ old('sale_price', $product->sale_price) }}"
                    class="w-full border rounded px-3 py-2"
                />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Giá vốn</label>
                <input
                    type="number"
                    name="cost_price"
                    min="0"
                    step="1"
                    value="{{ old('cost_price', $product->cost_price) }}"
                    class="w-full border rounded px-3 py-2"
                />
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1"
                    >Cân nặng (g)</label
                >
                <input
                    type="number"
                    name="weight_gram"
                    min="0"
                    value="{{ old('weight_gram', $product->weight_gram) }}"
                    class="w-full border rounded px-3 py-2"
                />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Dài (cm)</label>
                <input
                    type="number"
                    name="length_cm"
                    min="0"
                    step="0.01"
                    value="{{ old('length_cm', $product->length_cm) }}"
                    class="w-full border rounded px-3 py-2"
                />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Rộng (cm)</label>
                <input
                    type="number"
                    name="width_cm"
                    min="0"
                    step="0.01"
                    value="{{ old('width_cm', $product->width_cm) }}"
                    class="w-full border rounded px-3 py-2"
                />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Cao (cm)</label>
                <input
                    type="number"
                    name="height_cm"
                    min="0"
                    step="0.01"
                    value="{{ old('height_cm', $product->height_cm) }}"
                    class="w-full border rounded px-3 py-2"
                />
            </div>
        </div>
    </div>

    {{-- Cột phải --}}
    <div class="space-y-5">
        <div>
            <label class="block text-sm font-medium mb-1">Danh mục</label>
            <select name="category_id" class="w-full border rounded px-3 py-2">
                <option value="">- Chưa chọn -</option>
                @foreach ($categories as $c)
                    <option
                        value="{{ $c->id }}"
                        @selected (old('category_id', $product->category_id) == $c->id)
                    >
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Thương hiệu</label>
            <select name="brand_id" class="w-full border rounded px-3 py-2">
                <option value="">- Chưa chọn -</option>
                @foreach ($brands as $b)
                    <option
                        value="{{ $b->id }}"
                        @selected (old('brand_id', $product->brand_id) == $b->id)
                    >
                        {{ $b->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="space-y-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Thuộc tính sản phẩm</h3>
                <p class="text-xs text-gray-500">
                    Chọn thuộc tính và đánh dấu thuộc tính dùng để tạo biến thể.
                    Khi sửa sản phẩm, dropdown thêm biến thể cập nhật ngay mà không cần lưu trước.
                </p>
            </div>
            @php
                $assignedAttributeIds = array_map('intval', (array) old('attribute_ids', array_keys($assignedAttributeSettings)));
                $variationAttributeIds = array_map('intval', (array) old(
                    'variation_attribute_ids',
                    array_keys(array_filter($assignedAttributeSettings, fn ($settings) => $settings['is_variation']))
                ));
            @endphp
            @forelse ($attributes as $attribute)
                @php
                    $isAssigned = in_array($attribute->id, $assignedAttributeIds, true);
                @endphp
                <div class="rounded border border-gray-200 p-3">
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input
                            type="checkbox"
                            name="attribute_ids[]"
                            value="{{ $attribute->id }}"
                            data-attribute-assignment="{{ $attribute->id }}"
                            @if ($product->exists) x-model.number="assignedIds" @endif
                            @checked($isAssigned)
                        />
                        <span>{{ $attribute->name }}</span>
                        <span class="text-xs font-normal text-gray-500">
                            ({{ $attribute->values->pluck('value')->implode(', ') }})
                        </span>
                    </label>
                    <label class="ml-6 mt-2 flex items-center gap-2 text-xs text-gray-600">
                        <input
                            type="checkbox"
                            name="variation_attribute_ids[]"
                            value="{{ $attribute->id }}"
                            data-variation-attribute="{{ $attribute->id }}"
                            @if ($product->exists)
                                x-model.number="variationIds"
                                :disabled="!assignedIds.includes({{ $attribute->id }})"
                            @endif
                            @checked(in_array($attribute->id, $variationAttributeIds, true))
                            @disabled(!$isAssigned)
                        />
                        Dùng để chọn giá trị cho biến thể
                    </label>
                </div>
            @empty
                <p class="rounded border border-dashed p-3 text-sm text-gray-500">
                    Chưa có thuộc tính. Tạo thuộc tính trước để gán cho sản phẩm.
                </p>
            @endforelse
            @error ('attribute_ids')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error ('variation_attribute_ids')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Trạng thái *</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                @foreach (['draft'=>'Nháp','active'=>'Đang bán','inactive'=>'Ẩn'] as $k=>$v)
                    <option
                        value="{{ $k }}"
                        @selected (old('status', $product->status) === $k)
                    >
                        {{ $v }}
                    </option>
                @endforeach
            </select>
        </div>

        <script>
            document.querySelectorAll('[data-attribute-assignment]').forEach((assignment) => {
                const variation = document.querySelector(
                    `[data-variation-attribute="${assignment.dataset.attributeAssignment}"]`
                );

                assignment.addEventListener('change', () => {
                    variation.disabled = !assignment.checked;
                    if (!assignment.checked) {
                        variation.checked = false;
                    }
                });
            });
        </script>

        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_featured" value="0" />
            <input
                type="checkbox"
                name="is_featured"
                value="1"
                @checked (old('is_featured', $product->is_featured))
            />
            <span class="text-sm">Sản phẩm nổi bật</span>
        </label>

        <div>
            <label class="block text-sm font-medium mb-1">Ảnh (tối đa 8)</label>
            <input
                type="file"
                name="images[]"
                multiple
                accept="image/*"
                class="w-full text-sm"
            />
        </div>

        @if ($product->exists && $product->images->count())
            <div>
                <div class="text-sm font-medium mb-2">
                    Ảnh hiện có (tick để xoá)
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($product->images as $img)
                        <label
                            class="relative border rounded overflow-hidden cursor-pointer group"
                        >
                            <img
                                src="{{ $img->url }}"
                                class="w-full h-24 object-cover"
                            />
                            <input
                                type="checkbox"
                                name="remove_images[]"
                                value="{{ $img->id }}"
                                class="absolute top-1 left-1"
                            />
                            @if ($img->is_primary)
                                <span
                                    class="absolute bottom-1 right-1 bg-indigo-600 text-white text-[10px] px-1 rounded"
                                    >Chính</span
                                >
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<div class="mt-6 flex gap-3">
    <button
        class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700"
    >
        Lưu
    </button>
    <a
        href="{{ route('admin.products.index') }}"
        class="px-4 py-2 rounded border hover:bg-gray-50"
        >Huỷ</a
    >
</div>
