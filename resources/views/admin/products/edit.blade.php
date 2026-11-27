{{-- Mẫu giao diện chỉnh sửa sản phẩm trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Sửa sản phẩm')

@section ('content')
    <div class="bg-white rounded-xl shadow-sm p-6 max-w-7xl">
        <form
            id="product-edit-form"
            method="POST"
            action="{{ route('admin.products.update', $product) }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method ('PUT')
            @include ('admin.products._form')
        </form>
    </div>
@endsection
