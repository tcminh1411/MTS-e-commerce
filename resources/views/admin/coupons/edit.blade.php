{{-- Mẫu giao diện chỉnh sửa mã giảm giá trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('title', 'Sửa mã giảm giá')
@section ('page_title', 'Sửa mã giảm giá')

@section ('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Sửa mã "{{ $coupon->code }}"</h1>
            <p class="text-sm text-gray-500">
                <a
                    href="{{ route('admin.coupons.index') }}"
                    class="text-indigo-600 hover:underline"
                    >← Quay lại danh sách</a
                >
            </p>
        </div>
        <a
            href="{{ route('admin.coupons.usages', $coupon) }}"
            class="text-sm text-indigo-600 hover:underline"
        >
            Xem {{ $coupon->used_count }} lượt dùng →
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-4xl">
        <form
            method="POST"
            action="{{ route('admin.coupons.update', $coupon) }}"
        >
            @csrf
            @method ('PUT')
            @include ('admin.coupons._form', ['coupon' => $coupon])
        </form>
    </div>
@endsection
