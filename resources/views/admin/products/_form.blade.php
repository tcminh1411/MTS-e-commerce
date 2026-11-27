{{-- Mẫu giao diện form sản phẩm trong trang quản trị. --}}
@php
// Tính toán sẵn dữ liệu ở PHP để JS không bị lỗi cú pháp và trình soạn thảo không báo đỏ
$initialSelectedAttributes = array_map('intval', array_keys($assignedAttributeSettings ?? []));

$attributeMapData = [];
foreach ($attributes as $attr) {
$attributeMapData[$attr->id] = $attr->values->map(function ($val) {
return ['id' => (int) $val->id, 'value' => $val->value];
})->toArray();
}
@endphp

@if ($errors->any())
<div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
    <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Bọc toàn bộ bằng Alpine để share data giữa các cột và bảng biến thể --}}
<div data-selected-attributes='@json($initialSelectedAttributes)'
    data-existing-variants='@json($existingVariants ?? [])' data-attribute-map='@json($attributeMapData)'
    x-data="variantMatrix($el.dataset)" x-init="init()">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        {{-- CỘT TRÁI: THÔNG TIN CHÍNH --}}
        <div class="md:col-span-2 space-y-5">
            <div>
                <label class="block text-sm font-medium mb-1">Tên sản phẩm *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}"
                    class="w-full border rounded px-3 py-2" required />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $product->slug) }}"
                        class="w-full border rounded px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                        class="w-full border rounded px-3 py-2" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Mô tả ngắn</label>
                <textarea name="short_description" rows="2"
                    class="w-full border rounded px-3 py-2">{{ old('short_description', $product->short_description) }}</textarea>
            </div>

            <div x-data="{ 
                aiLoading: false,
                genDescription() {
                    let name = document.querySelector('input[name=name]').value;
                    let catSelect = document.querySelector('select[name=category_id]');
                    let category = catSelect.options[catSelect.selectedIndex]?.text || 'Sản phẩm';
                    if(!name) { alert('Vui lòng nhập tên sản phẩm trước!'); return; }
                    this.aiLoading = true;
                    fetch('{{ route('admin.products.generate-description') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ name: name, category: category })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.ok) { document.querySelector('textarea[name=description]').value = data.description; } 
                        else { alert('Lỗi: ' + data.message); }
                        this.aiLoading = false;
                    })
                    .catch(err => { alert('Lỗi mạng!'); this.aiLoading = false; });
                }
            }">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium">Mô tả chi tiết</label>
                    <button type="button" @click="genDescription()" :disabled="aiLoading"
                        class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-semibold rounded hover:bg-indigo-200 transition disabled:opacity-50 flex items-center gap-1">
                        <span x-show="!aiLoading">✨ Viết bằng AI</span>
                        <span x-show="aiLoading">Đang viết... ⏳</span>
                    </button>
                </div>
                <textarea name="description" rows="6"
                    class="w-full border rounded px-3 py-2">{{ old('description', $product->description) }}</textarea>
            </div>

            {{-- CÁC CHỈ SỐ CHUNG --}}
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Giá bán *</label>
                    <input type="number" name="price" min="0" step="1" value="{{ old('price', $product->price) }}"
                        class="w-full border rounded px-3 py-2" required />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Giá khuyến mãi</label>
                    <input type="number" name="sale_price" min="0" step="1"
                        value="{{ old('sale_price', $product->sale_price) }}" class="w-full border rounded px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Giá vốn</label>
                    <input type="number" name="cost_price" min="0" step="1"
                        value="{{ old('cost_price', $product->cost_price) }}" class="w-full border rounded px-3 py-2" />
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Cân nặng (g)</label>
                    <input type="number" name="weight_gram" min="0"
                        value="{{ old('weight_gram', $product->weight_gram) }}"
                        class="w-full border rounded px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Dài (cm)</label>
                    <input type="number" name="length_cm" min="0" step="0.01"
                        value="{{ old('length_cm', $product->length_cm) }}" class="w-full border rounded px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Rộng (cm)</label>
                    <input type="number" name="width_cm" min="0" step="0.01"
                        value="{{ old('width_cm', $product->width_cm) }}" class="w-full border rounded px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Cao (cm)</label>
                    <input type="number" name="height_cm" min="0" step="0.01"
                        value="{{ old('height_cm', $product->height_cm) }}" class="w-full border rounded px-3 py-2" />
                </div>
            </div>
        </div>

        {{-- CỘT PHẢI: THUỘC TÍNH VÀ CÀI ĐẶT --}}
        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium mb-1">Danh mục</label>
                <select name="category_id" class="w-full border rounded px-3 py-2">
                    <option value="">- Chưa chọn -</option>
                    @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected (old('category_id', $product->category_id) == $c->id)>
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
                    <option value="{{ $b->id }}" @selected (old('brand_id', $product->brand_id) == $b->id)>
                        {{ $b->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- KHỐI CHỌN THUỘC TÍNH --}}
            <div class="space-y-3 bg-gray-50 p-4 rounded-lg border">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Chọn Thuộc tính</h3>
                    <p class="text-xs text-gray-500">Tích các giá trị để tạo ma trận biến thể.</p>
                </div>

                @foreach ($attributes as $attribute)
                <div class="rounded border border-gray-200 p-3 bg-white">
                    <label class="flex items-center gap-2 text-sm font-medium cursor-pointer">
                        <input type="checkbox" name="attribute_ids[]" value="{{ $attribute->id }}"
                            x-model.number="selectedAttributes" @change="generateVariants()" />
                        <span>{{ $attribute->name }}</span>
                    </label>

                    <div class="ml-6 mt-2 flex flex-wrap gap-3"
                        x-show="selectedAttributes.includes({{ $attribute->id }})">
                        @foreach ($attribute->values as $value)
                        <label
                            class="inline-flex items-center gap-1 text-xs text-gray-700 cursor-pointer bg-gray-50 px-2 py-1 rounded border">
                            <input type="checkbox" value="{{ $value->id }}" x-model.number="selectedValues"
                                @change="generateVariants()" />
                            {{ $value->value }}
                        </label>
                        @endforeach
                    </div>
                    <input type="hidden" name="variation_attribute_ids[]" value="{{ $attribute->id }}"
                        x-bind:disabled="!selectedAttributes.includes({{ $attribute->id }})">
                </div>
                @endforeach
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Trạng thái *</label>
                <select name="status" class="w-full border rounded px-3 py-2">
                    @foreach (['draft'=>'Nháp','active'=>'Đang bán','inactive'=>'Ẩn'] as $k=>$v)
                    <option value="{{ $k }}" @selected (old('status', $product->status) === $k)>
                        {{ $v }}
                    </option>
                    @endforeach
                </select>
            </div>

            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="is_featured" value="0" />
                <input type="checkbox" name="is_featured" value="1" @checked (old('is_featured', $product->is_featured))
                />
                <span class="text-sm">Sản phẩm nổi bật</span>
            </label>

            <div>
                <label class="block text-sm font-medium mb-1">Ảnh (tối đa 8)</label>
                <input type="file" name="images[]" multiple accept="image/*" class="w-full text-sm" />
            </div>

            @if ($product->exists && $product->images->count())
            <div>
                <div class="text-sm font-medium mb-2">Ảnh hiện có (tick để xoá)</div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($product->images as $img)
                    <label class="relative border rounded overflow-hidden cursor-pointer group">
                        <img src="{{ $img->url }}" class="w-full h-24 object-cover" />
                        <input type="checkbox" name="remove_images[]" value="{{ $img->id }}"
                            class="absolute top-1 left-1" />
                        @if ($img->is_primary)
                        <span
                            class="absolute bottom-1 right-1 bg-indigo-600 text-white text-[10px] px-1 rounded">Chính</span>
                        @endif
                    </label>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- BẢNG MA TRẬN BIẾN THỂ --}}
    <div class="mt-8 border-t pt-5" x-show="variants.length > 0" x-cloak>
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-bold text-gray-800 text-lg">Danh sách biến thể được tạo</h4>
            <button type="button" @click="syncParentValues()"
                class="px-3 py-1.5 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded border border-yellow-300 hover:bg-yellow-200 transition">
                ⚡ Áp dụng chỉ số phía trên cho tất cả
            </button>
        </div>
        <div class="overflow-x-auto border rounded-lg shadow-sm">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 border-b">Phân loại</th>
                        <th class="px-4 py-3 border-b">SKU *</th>
                        <th class="px-4 py-3 border-b">Giá bán *</th>
                        <th class="px-4 py-3 border-b">Giá KM</th>
                        <th class="px-4 py-3 border-b">Giá vốn</th>
                        <th class="px-4 py-3 border-b">Cân nặng (g)</th>
                        <th class="px-4 py-3 border-b text-center">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(variant, index) in variants" :key="index">
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <span class="font-medium text-gray-800" x-text="variant.name"></span>
                                <input type="hidden" :name="`variants[${index}][id]`" :value="variant.id">
                                <input type="hidden" :name="`variants[${index}][name]`" :value="variant.name">
                                <template x-for="valId in variant.attribute_value_ids" :key="valId">
                                    <input type="hidden" :name="`variants[${index}][attribute_value_ids][]`"
                                        :value="valId">
                                </template>
                            </td>
                            <td class="px-4 py-2">
                                <input type="text" :name="`variants[${index}][sku]`" x-model="variant.sku" required
                                    class="w-full border rounded px-2 py-1.5" placeholder="Mã SKU">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" :name="`variants[${index}][price]`" x-model="variant.price"
                                    required class="w-full border rounded px-2 py-1.5">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" :name="`variants[${index}][sale_price]`"
                                    x-model="variant.sale_price" class="w-full border rounded px-2 py-1.5">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" :name="`variants[${index}][cost_price]`"
                                    x-model="variant.cost_price" class="w-full border rounded px-2 py-1.5">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" :name="`variants[${index}][weight_gram]`"
                                    x-model="variant.weight_gram" class="w-full border rounded px-2 py-1.5">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <select :name="`variants[${index}][status]`" x-model="variant.status"
                                    class="border rounded px-2 py-1.5">
                                    <option value="active">Đang bán</option>
                                    <option value="inactive">Ẩn</option>
                                </select>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-6 flex gap-3">
    <button class="px-5 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 font-medium">
        Lưu sản phẩm
    </button>
    <a href="{{ route('admin.products.index') }}" class="px-5 py-2 rounded border hover:bg-gray-50 font-medium">Huỷ</a>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('variantMatrix', (dataset) => ({
            selectedAttributes: JSON.parse(dataset.selectedAttributes),
            selectedValues: [],
            variants: [],
            existingVariants: JSON.parse(dataset.existingVariants),
            attributeMap: JSON.parse(dataset.attributeMap),

            init() {
                if (this.existingVariants.length > 0) {
                    this.existingVariants.forEach(v => {
                        v.attribute_value_ids.forEach(id => {
                            if (!this.selectedValues.includes(id)) this.selectedValues
                                .push(id);
                        });
                    });
                    this.variants = JSON.parse(JSON.stringify(this.existingVariants));
                }
            },

            getParentVal(selector) {
                let el = document.querySelector(selector);
                return el ? el.value : '';
            },

            syncParentValues() {
                let price = this.getParentVal('input[name=price]');
                let salePrice = this.getParentVal('input[name=sale_price]');
                let costPrice = this.getParentVal('input[name=cost_price]');
                let weight = this.getParentVal('input[name=weight_gram]');

                this.variants.forEach(v => {
                    if (price !== '') v.price = price;
                    if (salePrice !== '') v.sale_price = salePrice;
                    if (costPrice !== '') v.cost_price = costPrice;
                    if (weight !== '') v.weight_gram = weight;
                });
            },

            generateVariants() {
                let valueGroups = [];
                let selectedAttrs = this.selectedAttributes.map(Number);
                let selectedVals = this.selectedValues.map(Number);

                selectedAttrs.forEach(attrId => {
                    let vals = this.attributeMap[attrId].filter(v => selectedVals.includes(v
                        .id));
                    if (vals.length > 0) valueGroups.push(vals);
                });

                let combinations = valueGroups.length > 0 ? valueGroups.reduce((a, b) => a.flatMap(d =>
                    b.map(e => [d, e].flat()))) : [];

                let newVariants = [];
                let defaultPrice = this.getParentVal('input[name=price]') || 0;
                let defaultSale = this.getParentVal('input[name=sale_price]');
                let defaultCost = this.getParentVal('input[name=cost_price]');
                let defaultWeight = this.getParentVal('input[name=weight_gram]');

                combinations.forEach(combo => {
                    let valIds = combo.map(c => c.id);
                    let name = combo.map(c => c.value).join(' / ');

                    let existing = this.variants.find(v =>
                        v.attribute_value_ids.length === valIds.length &&
                        v.attribute_value_ids.every(id => valIds.includes(id))
                    );

                    if (existing) {
                        newVariants.push(existing);
                    } else {
                        let newSku = '{{ $product->sku ?: "P" }}' + '-' + Math.random()
                            .toString(36).substring(2, 6).toUpperCase();
                        newVariants.push({
                            id: '',
                            name: name,
                            sku: newSku,
                            price: defaultPrice,
                            sale_price: defaultSale,
                            cost_price: defaultCost,
                            weight_gram: defaultWeight,
                            status: 'active',
                            attribute_value_ids: valIds
                        });
                    }
                });
                this.variants = newVariants;
            }
        }));
    });
</script>