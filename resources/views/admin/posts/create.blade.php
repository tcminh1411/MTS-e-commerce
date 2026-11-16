{{-- Mẫu giao diện tạo mới bài viết trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Thêm bài viết')
@section ('page_title', 'Thêm bài viết')

@section ('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Thêm bài viết</h1>
            <p class="text-sm text-gray-500">
                <a href="{{ route('admin.posts.index') }}" class="text-indigo-600 hover:underline">
                    ← Quay lại danh sách
                </a>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            @include ('admin.posts._form')
        </div>
    </div>
@endsection
