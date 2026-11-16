{{-- Chỉnh sửa thuộc tính sản phẩm trong trang quản trị. --}}
@extends ('layouts.admin')
@section ('page_title', 'Sửa thuộc tính')

@section ('content')
    <div class="max-w-3xl rounded-xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.attributes.update', $attribute) }}">
            @csrf
            @method ('PUT')
            @include ('admin.attributes._form')
        </form>
    </div>
@endsection
