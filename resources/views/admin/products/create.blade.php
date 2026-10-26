{{-- Mẫu giao diện tạo mới sản phẩm trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Thêm sản phẩm')

@section ('content')
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
