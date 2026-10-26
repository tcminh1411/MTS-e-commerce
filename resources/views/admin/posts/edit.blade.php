{{-- Mẫu giao diện chỉnh sửa bài viết trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Sửa bài viết')
@section ('page_title', 'Sửa bài viết')

@section ('content')
    <div class="max-w-4xl">
        <div class="mb-4 flex items-center justify-between">
            <a
                href="{{ route('admin.posts.index') }}"
                class="text-sm text-indigo-600 hover:underline"
            >
                ← Danh sách bài viết
            </a>
            @if ($post->status === \App\Models\Post::STATUS_PUBLISHED)
                <a
                    href="{{ route('blog.show', $post) }}"
                    target="_blank"
                    class="text-sm text-gray-600 hover:underline"
                >
                    Xem trên storefront ↗
                </a>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                Sửa: <span class="text-indigo-600">{{ $post->title }}</span>
            </h2>
            @include ('admin.posts._form')
        </div>
    </div>
@endsection
