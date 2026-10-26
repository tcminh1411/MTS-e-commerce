{{-- Mẫu giao diện tạo mới mã giảm giá trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Thêm mã giảm giá')
@section ('page_title', 'Thêm mã giảm giá')

@section ('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Thêm mã giảm giá</h1>
        <p class="text-sm text-gray-500">
            <a
                href="{{ route('admin.coupons.index') }}"
                class="text-indigo-600 hover:underline"
                >← Quay lại danh sách</a
            >
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.coupons.store') }}">
            @csrf
            @include ('admin.coupons._form', ['coupon' => $coupon])
        </form>
    </div>
@endsection
