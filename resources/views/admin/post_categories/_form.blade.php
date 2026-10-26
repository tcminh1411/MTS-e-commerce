{{-- Mẫu giao diện form danh mục bài viết trong trang quản trị. --}}
@php
    $isEdit = isset($category) && $category->exists;
    $action = $isEdit ? route('admin.post_categories.update', $category) : route('admin.post_categories.store');
    $method = $isEdit ? 'PUT' : 'POST';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @method ($method)

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Tên danh mục <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            name="name"
            value="{{ old('name', $category->name ?? '') }}"
            class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            placeholder="VD: Tin công nghệ"
        />
        @error ('name')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Slug
            <span class="text-xs text-gray-400 font-normal"
                >— để trống sẽ tự sinh từ tên</span
            >
        </label>
        <input
            type="text"
            name="slug"
            value="{{ old('slug', $category->slug ?? '') }}"
            class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
            placeholder="tin-cong-nghe"
        />
        @error ('slug')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"
            >Mô tả</label
        >
        <textarea
            name="description"
            rows="3"
            class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            placeholder="Mô tả ngắn về danh mục (tối đa 500 ký tự)"
            >{{ old('description', $category->description ?? '') }}</textarea
        >
        @error ('description')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
                >Thứ tự</label
            >
            <input
                type="number"
                name="sort_order"
                min="0"
                max="9999"
                value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            />
            <p class="text-xs text-gray-400 mt-1">Số nhỏ hiện trước.</p>
            @error ('sort_order')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="text-sm text-gray-700">Hiển thị</span>
            </label>
        </div>
    </div>

    <div class="flex items-center gap-3 pt-3 border-t">
        <button
            type="submit"
            class="px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700"
        >
            {{ $isEdit ? 'Cập nhật' : 'Tạo danh mục' }}
        </button>
        <a
            href="{{ route('admin.post_categories.index') }}"
            class="px-4 py-2 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-50"
        >
            Huỷ
        </a>
    </div>
</form>
