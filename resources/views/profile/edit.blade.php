{{-- Mẫu giao diện chỉnh sửa hồ sơ người dùng. --}}
@extends ('layouts.storefront')

@section ('title', 'Tài khoản của tôi')

@section ('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <h1 class="font-serif text-3xl font-bold">Tài khoản của tôi</h1>
            <p class="text-sm text-muted mt-1">Quản lý thông tin cá nhân, mật khẩu và bảo mật.</p>
        </div>

        <div class="space-y-6">
            <div
                class="bg-surface rounded-2xl shadow-sm border border-gray-100 p-6"
            >
                @include ('profile.partials.update-profile-information-form')
            </div>

            <div
                class="bg-surface rounded-2xl shadow-sm border border-gray-100 p-6"
            >
                @include ('profile.partials.update-password-form')
            </div>

            <div
                class="bg-surface rounded-2xl shadow-sm border border-gray-100 p-6"
            >
                @include ('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection
