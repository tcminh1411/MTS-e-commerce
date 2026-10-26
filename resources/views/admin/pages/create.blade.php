{{-- Mẫu giao diện tạo mới trang tĩnh trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Thêm trang')
@section ('page_title', 'Thêm trang tĩnh')

@section ('content')
    <div class="max-w-4xl">
        <div class="mb-4">
            <a
                href="{{ route('admin.pages.index') }}"
                class="text-sm text-indigo-600 hover:underline"
            >
                ← Danh sách trang
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                Thêm trang tĩnh mới
            </h2>
            @include ('admin.pages._form')
        </div>
    </div>
@endsection
