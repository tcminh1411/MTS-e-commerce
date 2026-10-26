{{-- Mẫu giao diện chỉnh sửa kho hàng trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Sửa kho')
@section ('page_title', 'Sửa kho: ' . $warehouse->name)

@section ('content')
    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        <form
            method="POST"
            action="{{ route('admin.warehouses.update', $warehouse) }}"
        >
            @csrf
            @method ('PUT')
            @include ('admin.warehouses._form', ['warehouse' => $warehouse])
        </form>
    </div>
@endsection
