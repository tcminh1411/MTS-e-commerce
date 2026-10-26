{{-- Mẫu giao diện danh sách bài viết trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Bài viết')
@section ('page_title', 'Quản lý bài viết')

@section ('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">
                Danh sách bài viết blog
                <span class="text-gray-500">({{ $posts->total() }})</span>
            </h2>
            <a
                href="{{ route('admin.posts.create') }}"
                class="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
            >
                + Thêm bài viết
            </a>
        </div>

        <form
            method="GET"
            class="bg-white rounded-lg shadow p-4 flex flex-wrap items-end gap-3"
        >
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-600 mb-1"
                    >Tìm kiếm</label
                >
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tiêu đề hoặc slug..."
                    class="w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1"
                    >Danh mục</label
                >
                <select
                    name="category"
                    class="rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                    <option value="">Tất cả</option>
                    @foreach ($categories as $cat)
                        <option
                            value="{{ $cat->id }}"
                            {{ (int) request('category') === $cat->id ? 'selected' : '' }}
                        >
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1"
                    >Trạng thái</label
                >
                <select
                    name="status"
                    class="rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                    <option value="">Tất cả</option>
                    @foreach (\App\Models\Post::STATUSES as $value => $label)
                        <option
                            value="{{ $value }}"
                            {{ request('status') === $value ? 'selected' : '' }}
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button
                type="submit"
                class="px-4 py-2 rounded bg-gray-800 text-white text-sm hover:bg-gray-900"
            >
                Lọc
            </button>
            @if (request()->hasAny(['q', 'category', 'status']))
                <a
                    href="{{ route('admin.posts.index') }}"
                    class="px-4 py-2 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-50"
                >
                    Xoá lọc
                </a>
            @endif
        </form>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Thumbnail</th>
                        <th class="px-4 py-3 text-left">Tiêu đề</th>
                        <th class="px-4 py-3 text-left">Danh mục</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3 text-center">Views</th>
                        <th class="px-4 py-3 text-left">Ngày đăng</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($posts as $post)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">
                                #{{ $post->id }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($post->thumbnail)
                                    <img
                                        src="{{ $post->thumbnail_url }}"
                                        alt=""
                                        class="w-16 h-10 object-cover rounded border"
                                    />
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    class="font-medium text-gray-800 max-w-xs truncate"
                                    title="{{ $post->title }}"
                                >
                                    {{ $post->title }}
                                </div>
                                <div
                                    class="font-mono text-xs text-gray-500 truncate"
                                    title="{{ $post->slug }}"
                                >
                                    /blog/{{ $post->slug }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ $post->category->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($post->status === \App\Models\Post::STATUS_PUBLISHED)
                                    <span
                                        class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs"
                                    >
                                        Đã đăng
                                    </span>
                                @else
                                    <span
                                        class="inline-block px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 text-xs"
                                    >
                                        Nháp
                                    </span>
                                @endif
                            </td>
                            <td
                                class="px-4 py-3 text-center text-xs text-gray-600"
                            >
                                {{ number_format($post->view_count) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $post->published_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if ($post->status === \App\Models\Post::STATUS_PUBLISHED)
                                        <a
                                            href="{{ route('blog.show', $post) }}"
                                            target="_blank"
                                            class="text-xs text-gray-600 hover:underline"
                                            >Xem</a
                                        >
                                    @endif
                                    <a
                                        href="{{ route('admin.posts.edit', $post) }}"
                                        class="text-xs text-indigo-600 hover:underline"
                                        >Sửa</a
                                    >
                                    <form
                                        method="POST"
                                        action="{{ route('admin.posts.destroy', $post) }}"
                                        onsubmit="return confirm('Xoá bài viết &quot;{{ $post->title }}&quot;?');"
                                        class="inline"
                                    >
                                        @csrf
                                        @method ('DELETE')
                                        <button
                                            type="submit"
                                            class="text-xs text-red-600 hover:underline"
                                        >
                                            Xoá
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="8"
                                class="px-4 py-10 text-center text-gray-500 text-sm"
                            >
                                Chưa có bài viết nào.
                                <a
                                    href="{{ route('admin.posts.create') }}"
                                    class="text-indigo-600 hover:underline"
                                    >Tạo bài viết đầu tiên</a
                                >
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($posts->hasPages())
            <div>{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
