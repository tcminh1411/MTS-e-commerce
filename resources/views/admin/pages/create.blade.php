{{-- Mẫu giao diện tạo mới trang tĩnh trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Thêm trang')
@section ('page_title', 'Thêm trang tĩnh')

@section ('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Thêm trang tĩnh</h1>
            <p class="text-sm text-gray-500">
                <a href="{{ route('admin.pages.index') }}" class="text-indigo-600 hover:underline">
                    ← Quay lại danh sách
                </a>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            @include ('admin.pages._form')
        </div>
    </div>
@endsection
