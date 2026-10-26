{{-- Mẫu giao diện form mã giảm giá trong trang quản trị. --}}
@php $isEdit = $coupon->exists; @endphp

<div
    class="space-y-5"
    x-data="{ type: '{{ old('type', $coupon->type ?? 'percent') }}' }"
>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div>
            <label class="block font-medium mb-1"
                >Mã giảm giá <span class="text-red-500">*</span></label
            >
            <input
                type="text"
                name="code"
                value="{{ old('code', $coupon->code) }}"
                class="w-full border rounded-lg px-3 py-2 font-mono uppercase"
                placeholder="VD: SALE10"
                maxlength="50"
            />
            @error ('code')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="md:col-span-2">
            <label class="block font-medium mb-1"
                >Tên hiển thị <span class="text-red-500">*</span></label
            >
            <input
                type="text"
                name="name"
                value="{{ old('name', $coupon->name) }}"
                class="w-full border rounded-lg px-3 py-2"
                placeholder="VD: Giảm 10% đơn từ 100k"
            />
            @error ('name')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block font-medium mb-1">Mô tả</label>
        <textarea
            name="description"
            rows="2"
            class="w-full border rounded-lg px-3 py-2"
            placeholder="Ghi chú nội bộ"
            >{{ old('description', $coupon->description) }}</textarea
        >
        @error ('description')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div>
            <label class="block font-medium mb-1"
                >Loại giảm <span class="text-red-500">*</span></label
            >
            <select
                name="type"
                x-model="type"
                class="w-full border rounded-lg px-3 py-2"
            >
                <option value="percent">Phần trăm (%)</option>
                <option value="fixed">Số tiền cố định (₫)</option>
            </select>
            @error ('type')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block font-medium mb-1"
                >Giá trị <span class="text-red-500">*</span></label
            >
            <input
                type="number"
                name="value"
                min="1"
                value="{{ old('value', $coupon->value) }}"
                class="w-full border rounded-lg px-3 py-2"
            />
            @error ('value')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div x-show="type === 'percent'" x-transition>
            <label class="block font-medium mb-1"
                >Giảm tối đa <span class="text-red-500">*</span></label
            >
            <input
                type="number"
                name="max_discount_amount"
                min="0"
                value="{{ old('max_discount_amount', $coupon->max_discount_amount) }}"
                class="w-full border rounded-lg px-3 py-2"
                placeholder="VD: 100000"
            />
            @error ('max_discount_amount')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div>
            <label class="block font-medium mb-1">Đơn tối thiểu</label>
            <input
                type="number"
                name="min_order_amount"
                min="0"
                value="{{ old('min_order_amount', $coupon->min_order_amount ?? 0) }}"
                class="w-full border rounded-lg px-3 py-2"
            />
            @error ('min_order_amount')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Tổng lượt dùng</label>
            <input
                type="number"
                name="usage_limit"
                min="1"
                value="{{ old('usage_limit', $coupon->usage_limit) }}"
                class="w-full border rounded-lg px-3 py-2"
                placeholder="Không giới hạn"
            />
            @error ('usage_limit')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Lượt / mỗi user</label>
            <input
                type="number"
                name="usage_limit_per_user"
                min="1"
                value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}"
                class="w-full border rounded-lg px-3 py-2"
                placeholder="Không giới hạn"
            />
            @error ('usage_limit_per_user')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block font-medium mb-1">Bắt đầu</label>
            <input
                type="datetime-local"
                name="starts_at"
                value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}"
                class="w-full border rounded-lg px-3 py-2"
            />
            @error ('starts_at')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Kết thúc</label>
            <input
                type="datetime-local"
                name="ends_at"
                value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d\TH:i')) }}"
                class="w-full border rounded-lg px-3 py-2"
            />
            @error ('ends_at')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="is_active" value="0" />
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked (old('is_active', $coupon->is_active ?? true))
                class="rounded border-gray-300 text-indigo-600"
            />
            <span class="text-sm">Kích hoạt mã</span>
        </label>
    </div>

    <div class="flex gap-3 pt-3 border-t">
        <button
            type="submit"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium"
        >
            {{ $isEdit ? 'Cập nhật' : 'Thêm mới' }}
        </button>
        <a
            href="{{ route('admin.coupons.index') }}"
            class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg"
        >
            Hủy
        </a>
    </div>
</div>
