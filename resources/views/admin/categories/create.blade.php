{{-- Mẫu giao diện tạo mới danh mục trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Thêm danh mục')
@section ('page_title', 'Thêm danh mục')

@section ('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Thêm danh mục</h1>
        <p class="text-sm text-gray-500">
            <a href="{{ route('admin.categories.index') }}" class="text-indigo-600 hover:underline">
                ← Quay lại danh sách
            </a>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        <form
            method="POST"
            action="{{ route('admin.categories.store') }}"
            enctype="multipart/form-data"
        >
            @csrf
            @include ('admin.categories._form')
        </form>
    </div>
@endsection
