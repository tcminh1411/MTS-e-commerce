{{-- Mẫu giao diện form banner trong trang quản trị. --}}
@php
    $isEdit = $banner->exists;
    $action = $isEdit ? route('admin.banners.update', $banner) : route('admin.banners.store');
    $positions = \App\Models\Banner::POSITIONS;
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    class="space-y-5"
>
    @csrf
    @if ($isEdit)
        @method ('PUT')
    @endif

    {{-- Title --}}
    <div>
        <label class="block font-medium mb-1">Tiêu đề</label>
        <input
            type="text"
            name="title"
            value="{{ old('title', $banner->title) }}"
            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="VD: Khuyến mãi iPhone 15 Pro Max"
        />
        @error ('title')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Image current --}}
    @if ($isEdit && $banner->image)
        <div>
            <label class="block font-medium mb-1">Ảnh hiện tại</label>
            <img
                src="{{ $banner->image_url }}"
                alt="banner"
                class="rounded-lg border max-h-40 object-cover"
            />
            <p class="text-xs text-gray-500 mt-1 break-all">Path: <code>{{ $banner->image }}</code></p>
        </div>
    @endif

    {{-- Upload file --}}
    <div>
        <label class="block font-medium mb-1">
            Upload ảnh {{ $isEdit ? '(để trống nếu không đổi)' : '' }}
        </label>
        <input
            type="file"
            name="image_file"
            accept="image/*"
            class="w-full border rounded-lg px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:bg-indigo-50 file:text-indigo-700"
        />
        <p class="text-xs text-gray-500 mt-1">JPG/PNG/WEBP, tối đa 2MB. Kích thước gợi ý: 1200×400.</p>
        @error ('image_file')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Hoặc URL --}}
    <div>
        <label class="block font-medium mb-1"
            >Hoặc nhập URL ảnh (external)</label
        >
        <input
            type="text"
            name="image_url"
            value="{{ old('image_url') }}"
            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="https://example.com/image.jpg"
        />
        <p class="text-xs text-gray-500 mt-1">Nếu vừa upload vừa nhập URL → ưu tiên file upload.</p>
        @error ('image_url')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Link --}}
    <div>
        <label class="block font-medium mb-1">Link khi click</label>
        <input
            type="text"
            name="link"
            value="{{ old('link', $banner->link) }}"
            class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="VD: /products?q=iPhone hoặc https://..."
        />
        @error ('link')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Position + Sort --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block font-medium mb-1"
                >Vị trí <span class="text-red-500">*</span></label
            >
            <select
                name="position"
                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                @foreach ($positions as $key => $label)
                    <option
                        value="{{ $key }}"
                        @selected (old('position', $banner->position) === $key)
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error ('position')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block font-medium mb-1">Thứ tự hiển thị</label>
            <input
                type="number"
                name="sort_order"
                min="0"
                max="65535"
                value="{{ old('sort_order', $banner->sort_order) }}"
                class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
            <p class="text-xs text-gray-500 mt-1">Số nhỏ hiển thị trước.</p>
            @error ('sort_order')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Active --}}
    <div>
        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="is_active" value="0" />
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked (old('is_active', $banner->is_active ?? true))
                class="rounded border-gray-300 text-indigo-600"
            />
            <span class="text-sm">Hiển thị banner</span>
        </label>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3 pt-3 border-t">
        <button
            type="submit"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium"
        >
            {{ $isEdit ? 'Cập nhật' : 'Thêm mới' }}
        </button>
        <a
            href="{{ route('admin.banners.index') }}"
            class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg"
        >
            Hủy
        </a>
    </div>
</form>
