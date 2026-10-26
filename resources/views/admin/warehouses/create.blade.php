{{-- Mẫu giao diện tạo mới kho hàng trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Thêm kho')
@section ('page_title', 'Thêm kho')

@section ('content')
    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.warehouses.store') }}">
            @csrf
            @include ('admin.warehouses._form')
        </form>
    </div>
@endsection
