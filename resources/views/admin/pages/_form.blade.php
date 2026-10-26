{{-- Mẫu giao diện form trang tĩnh trong trang quản trị. --}}
@php
    $isEdit = isset($page) && $page->exists;
    $action = $isEdit ? route('admin.pages.update', $page) : route('admin.pages.store');
    $method = $isEdit ? 'PUT' : 'POST';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @method ($method)

    {{-- Title --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Tiêu đề <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            name="title"
            value="{{ old('title', $page->title ?? '') }}"
            class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            placeholder="VD: Giới thiệu"
        />
        @error ('title')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Slug --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Slug (URL)
            <span class="text-xs text-gray-400 font-normal"
                >— để trống sẽ tự sinh từ tiêu đề</span
            >
        </label>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">/page/</span>
            <input
                type="text"
                name="slug"
                value="{{ old('slug', $page->slug ?? '') }}"
                class="flex-1 rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
                placeholder="gioi-thieu"
            />
        </div>
        @error ('slug')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Body --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Nội dung (HTML) <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">Được phép dùng HTML cơ bản: <code class="bg-gray-100 px-1 rounded">&lt;h2&gt;</code>, <code class="bg-gray-100 px-1 rounded">&lt;p&gt;</code>, <code class="bg-gray-100 px-1 rounded">&lt;ul&gt;&lt;li&gt;</code>, <code class="bg-gray-100 px-1 rounded">&lt;strong&gt;</code>, <code class="bg-gray-100 px-1 rounded">&lt;a href="..."&gt;</code>.</p>
        <textarea
            name="body"
            rows="16"
            class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
            placeholder="<h2>Tiêu đề</h2>&#10;<p>Nội dung...</p>"
            >{{ old('body', $page->body ?? '') }}</textarea
        >
        @error ('body')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Meta --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
                >Meta title (SEO)</label
            >
            <input
                type="text"
                name="meta_title"
                value="{{ old('meta_title', $page->meta_title ?? '') }}"
                class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                placeholder="Để trống sẽ dùng tiêu đề"
            />
            @error ('meta_title')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
                >Meta description (SEO)</label
            >
            <input
                type="text"
                name="meta_description"
                value="{{ old('meta_description', $page->meta_description ?? '') }}"
                class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                placeholder="Tối đa 500 ký tự"
            />
            @error ('meta_description')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- is_active --}}
    <div>
        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                {{ old('is_active', $page->is_active ?? true) ? 'checked' : '' }}
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span class="text-sm text-gray-700">Hiển thị (active)</span>
        </label>
    </div>

    {{-- Footer --}}
    <div class="border-t pt-4">
        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input
                type="checkbox"
                name="show_in_footer"
                value="1"
                {{ old('show_in_footer', $page->show_in_footer ?? false) ? 'checked' : '' }}
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span class="text-sm text-gray-700">Hiện ở footer storefront</span>
        </label>
        <p class="text-xs text-gray-500 mt-1 ml-6">Nếu tick, link đến trang này sẽ xuất hiện ở footer (chỉ khi trang đang active).</p>

        <div class="mt-3 ml-6">
            <label class="block text-xs font-medium text-gray-600 mb-1"
                >Thứ tự hiển thị ở footer</label
            >
            <input
                type="number"
                name="footer_sort"
                min="0"
                max="9999"
                value="{{ old('footer_sort', $page->footer_sort ?? 0) }}"
                class="w-32 rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            />
            <span class="text-xs text-gray-400 ml-2">(số nhỏ hiện trước)</span>
            @error ('footer_sort')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>
    {{-- Actions --}}
    <div class="flex items-center gap-3 pt-3 border-t">
        <button
            type="submit"
            class="px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700"
        >
            {{ $isEdit ? 'Cập nhật' : 'Tạo trang' }}
        </button>
        <a
            href="{{ route('admin.pages.index') }}"
            class="px-4 py-2 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-50"
        >
            Huỷ
        </a>
    </div>
</form>
