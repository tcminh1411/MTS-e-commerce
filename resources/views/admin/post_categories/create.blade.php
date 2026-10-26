{{-- Mẫu giao diện tạo mới danh mục bài viết trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Thêm danh mục blog')
@section ('page_title', 'Thêm danh mục blog')

@section ('content')
    <div class="max-w-3xl">
        <div class="mb-4">
            <a
                href="{{ route('admin.post_categories.index') }}"
                class="text-sm text-indigo-600 hover:underline"
            >
                ← Danh sách danh mục
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                Thêm danh mục blog mới
            </h2>
            @include ('admin.post_categories._form')
        </div>
    </div>
@endsection
