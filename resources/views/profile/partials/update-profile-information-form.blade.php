{{-- Biểu mẫu cập nhật thông tin hồ sơ. --}}
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form
        id="send-verification"
        method="post"
        action="{{ route('verification.send') }}"
    >
        @csrf
    </form>

    <form
        method="post"
        action="{{ route('profile.update') }}"
        enctype="multipart/form-data"
        class="mt-6 space-y-6"
    >
        @csrf
        @method ('patch')

        <div>
            <x-input-label for="name" :value="__('Họ tên')" />
            <x-text-input
                id="name"
                name="full_name"
                type="text"
                class="mt-1 block w-full"
                :value="old('full_name', $user->full_name)"
                required
                autofocus
                autocomplete="name"
            />
            <x-input-error class="mt-2" :messages="$errors->get('full_name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-1 block w-full"
                :value="old('email', $user->email)"
                required
                autocomplete="username"
            />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button
                            form="send-verification"
                            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="mt-4">
            <x-input-label for="avatar" :value="__('Ảnh đại diện')" />

            @if ($user->avatar_url_full)
                <div class="mt-2 mb-2 flex items-center gap-3">
                    <img
                        src="{{ $user->avatar_url_full }}"
                        alt="avatar"
                        class="w-16 h-16 rounded-full object-cover border"
                    />
                    <span class="text-xs text-gray-500">Ảnh hiện tại</span>
                </div>
            @endif

            <input
                type="file"
                id="avatar"
                name="avatar"
                accept="image/*"
                class="mt-1 block w-full text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:bg-indigo-50 file:text-indigo-700"
            />
            <p class="text-xs text-gray-500 mt-1">JPG/PNG/WEBP, tối đa 2MB.</p>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="gender" :value="__('Giới tính')" />
                <select
                    id="gender"
                    name="gender"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option value="">— Chưa chọn —</option>
                    <option
                        value="male"
                        @selected (old('gender', $user->gender) === 'male')
                    >
                        Nam
                    </option>
                    <option
                        value="female"
                        @selected (old('gender', $user->gender) === 'female')
                    >
                        Nữ
                    </option>
                    <option
                        value="other"
                        @selected (old('gender', $user->gender) === 'other')
                    >
                        Khác
                    </option>
                </select>
                <x-input-error
                    class="mt-2"
                    :messages="$errors->get('gender')"
                />
            </div>

            <div>
                <x-input-label for="birthday" :value="__('Ngày sinh')" />
                <x-text-input
                    id="birthday"
                    name="birthday"
                    type="date"
                    class="mt-1 block w-full"
                    :value="old('birthday', $user->birthday?->format('Y-m-d'))"
                />
                <x-input-error
                    class="mt-2"
                    :messages="$errors->get('birthday')"
                />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => (show = false), 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
