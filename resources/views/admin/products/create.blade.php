{{-- Mẫu giao diện tạo mới sản phẩm trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Thêm sản phẩm')
@section ('page_title', 'Thêm sản phẩm')

@section ('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Thêm sản phẩm</h1>
        <p class="text-sm text-gray-500">
            <a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:underline">
                ← Quay lại danh sách
            </a>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <form
            method="POST"
            action="{{ route('admin.products.store') }}"
            enctype="multipart/form-data"
        >
            @csrf
            @include ('admin.products._form')
        </form>
    </div>
@endsection
