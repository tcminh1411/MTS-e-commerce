{{-- Tạo thuộc tính sản phẩm trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Thêm thuộc tính')
@section ('page_title', 'Thêm thuộc tính')

@section ('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Thêm thuộc tính</h1>
        <p class="text-sm text-gray-500">
            <a href="{{ route('admin.attributes.index') }}" class="text-indigo-600 hover:underline">
                ← Quay lại danh sách
            </a>
        </p>
    </div>

    <div class="max-w-3xl rounded-xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.attributes.store') }}">
            @csrf
            @include ('admin.attributes._form')
        </form>
    </div>
@endsection
