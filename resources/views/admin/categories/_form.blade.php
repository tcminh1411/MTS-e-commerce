{{-- Mẫu giao diện form danh mục trong trang quản trị. --}}
<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block font-medium mb-1"
                >Tên danh mục <span class="text-red-500">*</span></label
            >
            <input
                type="text"
                name="name"
                value="{{ old('name', $category->name) }}"
                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
            />
            @error ('name')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block font-medium mb-1"
                >Slug
                <span class="text-xs text-gray-500"
                    >(để trống tự sinh)</span
                ></label
            >
            <input
                type="text"
                name="slug"
                value="{{ old('slug', $category->slug) }}"
                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
            @error ('slug')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block font-medium mb-1">Danh mục cha</label>
            <select
                name="parent_id"
                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                <option value="">— Không có (danh mục gốc) —</option>
                @foreach ($parents as $p)
                    <option
                        value="{{ $p->id }}"
                        @selected (old('parent_id', $category->parent_id) == $p->id)
                    >
                        {{ $p->parent_id ? '— ' : '' }}{{ $p->name }}
                    </option>
                @endforeach
            </select>
            @error ('parent_id')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Thứ tự</label>
            <input
                type="number"
                name="sort_order"
                min="0"
                value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
            @error ('sort_order')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block font-medium mb-1">Mô tả</label>
        <textarea
            name="description"
            rows="3"
            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >{{ old('description', $category->description) }}</textarea
        >
        @error ('description')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block font-medium mb-1">Ảnh</label>
            <input
                type="file"
                name="image"
                accept="image/*"
                class="w-full text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700"
            />
            @if ($category->image_url)
                <div class="mt-2 flex items-center gap-2">
                    <img
                        src="{{ asset('storage/' . $category->image_url) }}"
                        class="h-16 rounded border"
                    />
                    <span class="text-xs text-gray-500">Ảnh hiện tại</span>
                </div>
            @endif
            @error ('image')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex items-end pb-2">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="is_active" value="0" />
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked (old('is_active', $category->is_active ?? true))
                    class="rounded border-gray-300 text-indigo-600"
                />
                <span class="text-sm">Đang hoạt động</span>
            </label>
        </div>
    </div>

    <div class="flex gap-3 pt-3 border-t">
        <button
            type="submit"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium"
        >
            Lưu
        </button>
        <a
            href="{{ route('admin.categories.index') }}"
            class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg"
            >Huỷ</a
        >
    </div>
</div>
