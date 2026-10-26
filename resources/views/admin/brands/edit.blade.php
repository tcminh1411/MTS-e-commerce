{{-- Mẫu giao diện chỉnh sửa thương hiệu trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Sửa thương hiệu')

@section ('content')
    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        <form
            method="POST"
            action="{{ route('admin.brands.update', $brand) }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method ('PUT')
            @include ('admin.brands._form')
        </form>
    </div>
@endsection
