{{-- Mẫu giao diện chỉnh sửa banner trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Sửa banner')
@section ('page_title', 'Sửa banner')

@section ('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Sửa banner #{{ $banner->id }}</h1>
        <p class="text-sm text-gray-500">
            <a
                href="{{ route('admin.banners.index') }}"
                class="text-indigo-600 hover:underline"
                >← Quay lại danh sách</a
            >
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        @include ('admin.banners._form', ['banner' => $banner])
    </div>
@endsection
