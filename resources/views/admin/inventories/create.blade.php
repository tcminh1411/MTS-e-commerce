@extends ('layouts.admin')
@section ('title', 'Thêm nhập / xuất kho')
@section ('page_title', 'Thêm nhập / xuất kho')

@section ('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Thêm nhập / xuất kho hàng loạt</h1>
    <p class="text-sm text-gray-500">
        <a href="{{ route('admin.inventories.index') }}" class="text-indigo-600 hover:underline">← Quay lại danh sách</a>
    </p>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-6xl">
    {{-- Dùng dấu nháy đơn (') cho x-data để không bị xung đột với dấu ngoặc kép của JSON --}}
    <form method="POST" action="{{ route('admin.inventories.store') }}" x-data='inventoryBulkForm(@json($products))'>
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 pb-6 border-b">
            <div>
                <label class="block text-sm font-medium mb-1" for="warehouse_id">Kho <span class="text-red-500">*</span></label>
                <select name="warehouse_id" id="warehouse_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">- Chọn kho -</option>
                    @foreach ($warehouses as $w)
                    <option value="{{ $w->id }}" @selected (old('warehouse_id', $selectedWarehouseId)==$w->id)>{{ $w->code }} - {{ $w->name }}</option>
                    @endforeach
                </select>
                @error ('warehouse_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Loại thao tác <span class="text-red-500">*</span></label>
                <div class="flex flex-row items-center gap-4 lg:gap-6 mt-1 flex-wrap lg:flex-nowrap">
                    <label class="inline-flex items-center gap-2 cursor-pointer whitespace-nowrap">
                        <input type="radio" name="type" value="in" required {{ old('type', 'in') === 'in' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700">Nhập kho (+)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer whitespace-nowrap">
                        <input type="radio" name="type" value="out" {{ old('type') === 'out' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700">Xuất kho (-)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer whitespace-nowrap">
                        <input type="radio" name="type" value="adjust" {{ old('type') === 'adjust' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700">Điều chỉnh (Đặt lại)</span>
                    </label>
                </div>
                @error ('type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold mb-2">1. Chọn Sản phẩm để thêm biến thể</label>
            <div class="flex gap-2 items-start">
                <select x-model="selectedProductId" @change="resetVariants()" class="w-full md:w-1/2 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">- Chọn sản phẩm -</option>
                    <template x-for="p in productsData" :key="p.id">
                        <option :value="p.id" x-text="p.name"></option>
                    </template>
                </select>
                <button type="button" x-show="selectedProductId" @click="addToCart()" class="px-4 py-2 bg-gray-800 text-white rounded text-sm hover:bg-gray-900 whitespace-nowrap">
                    + Thêm vào danh sách
                </button>
            </div>
        </div>

        <div x-show="selectedProductId" x-cloak class="border rounded-lg overflow-hidden mb-8 shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left w-12">
                            <input type="checkbox" @change="toggleAll($event)" :checked="isAllSelected" class="rounded border-gray-300 text-indigo-600" />
                        </th>
                        <th class="px-4 py-3 text-left">Biến thể</th>
                        <th class="px-4 py-3 text-left">SKU</th>
                        <th class="px-4 py-3 text-left w-40">Số lượng</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <template x-for="variant in availableVariants" :key="variant.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <input type="checkbox" :value="variant.id" x-model="selectedVariantIds" @change="toggleVariant(variant.id)" class="rounded border-gray-300 text-indigo-600" />
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-800" x-text="variant.name"></td>
                            <td class="px-4 py-3 text-gray-500 font-mono text-xs" x-text="variant.sku"></td>
                            <td class="px-4 py-3">
                                <input type="number" min="1" x-model="quantities[variant.id]" :disabled="!selectedVariantIds.includes(variant.id)"
                                    class="w-full border rounded px-2 py-1.5 disabled:bg-gray-100 disabled:text-gray-400 text-sm" placeholder="0" />
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="cart.length > 0" x-cloak class="mb-6">
            <h3 class="text-sm font-semibold mb-2 text-indigo-700">2. Danh sách chờ xử lý (<span x-text="cart.length"></span> biến thể)</h3>
            <div class="border border-indigo-200 rounded-lg overflow-hidden bg-indigo-50/30">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-100/50 text-indigo-800">
                        <tr>
                            <th class="px-4 py-2 text-left">Sản phẩm</th>
                            <th class="px-4 py-2 text-left">Phân loại</th>
                            <th class="px-4 py-2 text-left">SKU</th>
                            <th class="px-4 py-2 text-center w-24">Số lượng</th>
                            <th class="px-4 py-2 text-center w-16"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-indigo-100 bg-white">
                        <template x-for="(item, index) in cart" :key="item.id">
                            <tr>
                                <td class="px-4 py-2 font-medium" x-text="item.product_name"></td>
                                <td class="px-4 py-2" x-text="item.name"></td>
                                <td class="px-4 py-2 text-gray-500 font-mono text-xs" x-text="item.sku"></td>
                                <td class="px-4 py-2 text-center font-bold text-indigo-700">
                                    <span x-text="item.quantity"></span>
                                    <input type="hidden" :name="`variants[${index}][id]`" :value="item.id">
                                    <input type="hidden" :name="`variants[${index}][quantity]`" :value="item.quantity">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <button type="button" @click="removeFromCart(index)" class="text-red-500 hover:text-red-700 text-xs font-medium">Xoá</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        @error ('variants') <p class="text-sm text-red-600 mb-4">{{ $message }}</p> @enderror

        <div class="border-t pt-4">
            <label class="block text-sm font-medium mb-1" for="note">Ghi chú chung</label>
            <textarea name="note" id="note" rows="2" maxlength="500" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('note') }}</textarea>
        </div>

        <div class="mt-6 flex items-center gap-3 border-t pt-5">
            <button type="submit" :disabled="cart.length === 0" class="px-5 py-2 rounded bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Lưu thay đổi
            </button>
            <a href="{{ route('admin.inventories.index') }}" class="px-5 py-2 rounded border text-sm text-gray-700 hover:bg-gray-50">Huỷ</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('inventoryBulkForm', (initialProducts) => ({
            selectedProductId: '',
            selectedVariantIds: [],
            quantities: {},
            productsData: initialProducts || [],
            cart: [],

            get availableVariants() {
                if (!this.selectedProductId) return [];
                let product = this.productsData.find(p => String(p.id) === String(this.selectedProductId));
                return product ? product.variants : [];
            },

            get isAllSelected() {
                return this.availableVariants.length > 0 && this.selectedVariantIds.length === this.availableVariants.length;
            },

            toggleAll(e) {
                if (e.target.checked) {
                    this.selectedVariantIds = this.availableVariants.map(v => String(v.id));
                    this.availableVariants.forEach(v => {
                        let vid = String(v.id);
                        if(!this.quantities[vid] || this.quantities[vid] == 0) this.quantities[vid] = 1;
                    });
                } else {
                    this.selectedVariantIds = [];
                }
            },

            toggleVariant(vId) {
                vId = String(vId);
                // Tự động điền số 1 nếu ô số lượng đang trống
                if (this.selectedVariantIds.includes(vId)) {
                    if (!this.quantities[vId] || this.quantities[vId] == 0) {
                        this.quantities[vId] = 1;
                    }
                }
            },

            resetVariants() {
                this.selectedVariantIds = [];
                this.quantities = {};
            },

            addToCart() {
                if (!this.selectedProductId) return;
                if (this.selectedVariantIds.length === 0) {
                    alert('Vui lòng tích chọn ít nhất 1 biến thể.');
                    return;
                }

                let product = this.productsData.find(p => String(p.id) === String(this.selectedProductId));
                if (!product) return;

                let addedCount = 0;
                this.selectedVariantIds.forEach(vId => {
                    vId = String(vId);
                    let qty = parseInt(this.quantities[vId]);
                    
                    if (isNaN(qty) || qty <= 0) return; // Bỏ qua nếu chưa nhập số lượng

                    let variant = product.variants.find(v => String(v.id) === vId);
                    if (!variant) return;

                    let existing = this.cart.find(c => String(c.id) === vId);
                    if (existing) {
                        existing.quantity += qty;
                    } else {
                        this.cart.push({
                            id: vId,
                            name: variant.name,
                            sku: variant.sku,
                            product_name: product.name,
                            quantity: qty
                        });
                    }
                    addedCount++;
                });

                if (addedCount > 0) {
                    this.selectedProductId = '';
                    this.resetVariants();
                } else {
                    alert('Vui lòng nhập số lượng lớn hơn 0 cho các biến thể đã chọn.');
                }
            },

            removeFromCart(index) {
                this.cart.splice(index, 1);
            }
        }));
    });
</script>
@endsection
