{{-- Mẫu giao diện tạo mới kho hàng trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Thêm kho')
@section ('page_title', 'Thêm kho')

@section ('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Thêm kho</h1>
        <p class="text-sm text-gray-500">
            <a href="{{ route('admin.warehouses.index') }}" class="text-indigo-600 hover:underline">
                ← Quay lại danh sách
            </a>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.warehouses.store') }}">
            @csrf
            @include ('admin.warehouses._form')
        </form>
    </div>
@endsection
