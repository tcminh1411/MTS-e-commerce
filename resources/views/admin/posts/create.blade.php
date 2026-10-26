{{-- Mẫu giao diện tạo mới bài viết trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Thêm bài viết')
@section ('page_title', 'Thêm bài viết')

@section ('content')
    <div class="max-w-4xl">
        <div class="mb-4">
            <a
                href="{{ route('admin.posts.index') }}"
                class="text-sm text-indigo-600 hover:underline"
            >
                ← Danh sách bài viết
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                Thêm bài viết mới
            </h2>
            @include ('admin.posts._form')
        </div>
    </div>
@endsection
