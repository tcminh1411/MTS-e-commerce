{{-- Mẫu giao diện tạo mới thương hiệu trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Thêm thương hiệu')

@section ('content')
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
