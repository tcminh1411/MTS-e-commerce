{{-- Mẫu giao diện chỉnh sửa danh mục bài viết trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Sửa danh mục blog')
@section ('page_title', 'Sửa danh mục blog')

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
                Sửa: <span class="text-indigo-600">{{ $category->name }}</span>
            </h2>
            @include ('admin.post_categories._form')
        </div>
    </div>
@endsection
