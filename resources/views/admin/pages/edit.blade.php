{{-- Mẫu giao diện chỉnh sửa trang tĩnh trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Sửa trang')
@section ('page_title', 'Sửa trang tĩnh')

@section ('content')
    <div class="max-w-4xl">
        <div class="mb-4 flex items-center justify-between">
            <a
                href="{{ route('admin.pages.index') }}"
                class="text-sm text-indigo-600 hover:underline"
            >
                ← Danh sách trang
            </a>
            <a
                href="{{ route('page.show', $page) }}"
                target="_blank"
                class="text-sm text-gray-600 hover:underline"
            >
                Xem trên storefront ↗
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                Sửa: <span class="text-indigo-600">{{ $page->title }}</span>
            </h2>
            @include ('admin.pages._form')
        </div>
    </div>
@endsection
