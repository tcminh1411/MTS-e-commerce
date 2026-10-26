{{-- Mẫu giao diện tạo mới danh mục trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Thêm danh mục')

@section ('content')
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
