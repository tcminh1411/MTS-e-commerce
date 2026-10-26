{{-- Mẫu giao diện form bài viết trong trang quản trị. --}}
@php
    $isEdit = isset($post) && $post->exists;
    $action = $isEdit ? route('admin.posts.update', $post) : route('admin.posts.store');
    $method = $isEdit ? 'PUT' : 'POST';
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    class="space-y-5"
>
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
            value="{{ old('title', $post->title ?? '') }}"
            class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            placeholder="VD: Top 5 smartphone đáng mua nhất 2026"
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
            <span class="text-xs text-gray-500">/blog/</span>
            <input
                type="text"
                name="slug"
                value="{{ old('slug', $post->slug ?? '') }}"
                class="flex-1 rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
                placeholder="top-5-smartphone-2026"
            />
        </div>
        @error ('slug')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Row: category + status --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
                >Danh mục</label
            >
            <select
                name="post_category_id"
                class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            >
                <option value="">— Chưa phân loại —</option>
                @foreach ($categories as $cat)
                    <option
                        value="{{ $cat->id }}"
                        {{ (int) old('post_category_id', $post->post_category_id ?? 0) === $cat->id ? 'selected' : '' }}
                    >
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error ('post_category_id')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
                >Trạng thái</label
            >
            <select
                name="status"
                class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            >
                @foreach (\App\Models\Post::STATUSES as $value => $label)
                    <option
                        value="{{ $value }}"
                        {{ old('status', $post->status ?? 'draft') === $value ? 'selected' : '' }}
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error ('status')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- published_at --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Ngày đăng
            <span class="text-xs text-gray-400 font-normal"
                >— để trống sẽ tự lấy thời điểm hiện tại khi đăng</span
            >
        </label>
        <input
            type="datetime-local"
            name="published_at"
            value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i') ?? '') }}"
            class="rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        />
        @error ('published_at')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Excerpt --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"
            >Tóm tắt</label
        >
        <textarea
            name="excerpt"
            rows="2"
            class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            placeholder="Tối đa 500 ký tự"
            >{{ old('excerpt', $post->excerpt ?? '') }}</textarea
        >
        @error ('excerpt')
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
            rows="14"
            class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
            placeholder="<p>Nội dung bài viết...</p>"
            >{{ old('body', $post->body ?? '') }}</textarea
        >
        @error ('body')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Thumbnail --}}
    <div class="border-t pt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1"
            >Thumbnail</label
        >
        <p class="text-xs text-gray-500 mb-2">Upload file ảnh <strong>HOẶC</strong> dán URL external. Nếu cả 2 trống và đang edit → giữ ảnh cũ.</p>

        @if ($isEdit && $post->thumbnail)
            <div class="mb-3 p-2 bg-gray-50 rounded inline-block">
                <img
                    src="{{ $post->thumbnail_url }}"
                    alt="thumbnail"
                    class="max-h-32 rounded border"
                />
                <p class="text-xs text-gray-500 mt-1 font-mono break-all">
                    Hiện tại: {{ $post->thumbnail }}
                </p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1"
                    >Upload file (max 2MB)</label
                >
                <input
                    type="file"
                    name="thumbnail_file"
                    accept="image/*"
                    class="w-full text-sm"
                />
                @error ('thumbnail_file')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1"
                    >Hoặc URL external</label
                >
                <input
                    type="text"
                    name="thumbnail_url"
                    value="{{ old('thumbnail_url') }}"
                    class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
                    placeholder="https://picsum.photos/..."
                />
                @error ('thumbnail_url')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Meta --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
                >Meta title (SEO)</label
            >
            <input
                type="text"
                name="meta_title"
                value="{{ old('meta_title', $post->meta_title ?? '') }}"
                class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
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
                value="{{ old('meta_description', $post->meta_description ?? '') }}"
                class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                placeholder="Tối đa 500 ký tự"
            />
            @error ('meta_description')
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
            {{ $isEdit ? 'Cập nhật' : 'Tạo bài viết' }}
        </button>
        <a
            href="{{ route('admin.posts.index') }}"
            class="px-4 py-2 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-50"
        >
            Huỷ
        </a>
    </div>
</form>
