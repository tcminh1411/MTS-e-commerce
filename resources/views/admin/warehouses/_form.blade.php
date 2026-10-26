{{-- Mẫu giao diện form kho hàng trong trang quản trị. --}}
@php ($w = $warehouse ?? null)

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium mb-1" for="name"
            >Tên kho <span class="text-red-500">*</span></label
        >
        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $w->name ?? '') }}"
            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            maxlength="100"
            required
        />
        @error ('name')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1" for="code"
            >Mã kho <span class="text-red-500">*</span></label
        >
        <input
            type="text"
            name="code"
            id="code"
            value="{{ old('code', $w->code ?? '') }}"
            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            maxlength="30"
            required
        />
        <p class="text-xs text-gray-500 mt-1">VD: HN-01, HCM-01 — dùng để phân biệt kho, không đổi sau khi tạo.</p>
        @error ('code')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1" for="phone"
            >Số điện thoại</label
        >
        <input
            type="text"
            name="phone"
            id="phone"
            value="{{ old('phone', $w->phone ?? '') }}"
            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            maxlength="30"
        />
        @error ('phone')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1" for="address"
            >Địa chỉ</label
        >
        <textarea
            name="address"
            id="address"
            rows="3"
            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >{{ old('address', $w->address ?? '') }}</textarea
        >
        @error ('address')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="inline-flex items-center gap-2">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                {{ old('is_active', $w->is_active ?? true) ? 'checked' : '' }}
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span class="text-sm">Đang hoạt động</span>
        </label>
        @error ('is_active')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button
        type="submit"
        class="px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700"
    >
        {{ $w ? 'Cập nhật' : 'Thêm kho' }}
    </button>
    <a
        href="{{ route('admin.warehouses.index') }}"
        class="px-4 py-2 rounded border text-sm hover:bg-gray-50"
        >Huỷ</a
    >
</div>
