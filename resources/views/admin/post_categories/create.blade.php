{{-- Mẫu giao diện tạo mới danh mục bài viết trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Thêm danh mục blog')
@section ('page_title', 'Thêm danh mục blog')

@section ('content')
    <div class="max-w-3xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Thêm danh mục blog</h1>
            <p class="text-sm text-gray-500">
                <a href="{{ route('admin.post_categories.index') }}" class="text-indigo-600 hover:underline">
                    ← Quay lại danh sách
                </a>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            @include ('admin.post_categories._form')
        </div>
    </div>
@endsection
