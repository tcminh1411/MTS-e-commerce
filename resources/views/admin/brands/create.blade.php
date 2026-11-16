{{-- Mẫu giao diện tạo mới thương hiệu trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Thêm thương hiệu')
@section ('page_title', 'Thêm thương hiệu')

@section ('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Thêm thương hiệu</h1>
        <p class="text-sm text-gray-500">
            <a href="{{ route('admin.brands.index') }}" class="text-indigo-600 hover:underline">
                ← Quay lại danh sách
            </a>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        <form
            method="POST"
            action="{{ route('admin.brands.store') }}"
            enctype="multipart/form-data"
        >
            @csrf
            @include ('admin.brands._form')
        </form>
    </div>
@endsection
