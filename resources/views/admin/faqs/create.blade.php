{{-- Mẫu giao diện tạo mới câu hỏi thường gặp trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Thêm FAQ')
@section ('page_title', 'Thêm FAQ')

@section ('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Thêm FAQ mới</h1>
        <p class="text-sm text-gray-500">
            <a
                href="{{ route('admin.faqs.index') }}"
                class="text-indigo-600 hover:underline"
                >← Quay lại danh sách</a
            >
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        @include ('admin.faqs._form', ['faq' => $faq])
    </div>
@endsection
