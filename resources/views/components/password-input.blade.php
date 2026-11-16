{{-- Component trường mật khẩu có nút hiện/ẩn. --}}
<div x-data="{ visible: false }" class="relative w-full">
    <input
        type="password"
        x-bind:type="visible ? 'text' : 'password'"
        {{ $attributes->merge(['class' => 'w-full pr-10']) }}
    />
    <button
        type="button"
        @click="visible = !visible"
        :aria-label="visible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
        :aria-pressed="visible.toString()"
        class="absolute top-0 right-0 bottom-0 z-10 flex items-center px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-r-md"
    >
        <svg
            x-show="!visible"
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            />
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
            />
        </svg>
        <svg
            x-show="visible"
            x-cloak
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 3l18 18M10.584 10.587a2 2 0 002.829 2.829"
            />
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9.88 5.09A10.94 10.94 0 0112 4.9c4.478 0 8.268 2.943 9.542 7a11.05 11.05 0 01-3.04 4.592M6.61 6.61A11.05 11.05 0 002.458 12c1.274 4.057 5.065 7 9.542 7 1.17 0 2.29-.182 3.332-.518"
            />
        </svg>
    </button>
</div>
