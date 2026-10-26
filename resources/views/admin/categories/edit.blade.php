{{-- Mẫu giao diện chỉnh sửa danh mục trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Sửa danh mục')

@section ('content')
    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        <form
            method="POST"
            action="{{ route('admin.categories.update', $category) }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method ('PUT')
            @include ('admin.categories._form')
        </form>
    </div>
@endsection
