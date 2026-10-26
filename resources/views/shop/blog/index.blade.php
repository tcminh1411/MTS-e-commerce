{{-- Danh sách bài viết blog. --}}
@extends ('layouts.storefront')

@section ('title', 'Blog — Tin tức & Hướng dẫn')

@section ('content')
    <div class="space-y-6">
        {{-- Header --}}
        <header class="border-b pb-4">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Blog</h1>
            <p class="text-sm text-gray-500 mt-1">Tin công nghệ, đánh giá sản phẩm và hướng dẫn sử dụng.</p>
        </header>

        {{-- Filter --}}
        <div class="flex flex-wrap items-center gap-3">
            <a
                href="{{ route('blog.index') }}"
                class="px-3 py-1.5 rounded-full text-sm border
                {{ !request('category') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
            >
                Tất cả
            </a>
            @foreach ($categories as $cat)
                <a
                    href="{{ route('blog.index', ['category' => $cat->slug]) }}"
                    class="px-3 py-1.5 rounded-full text-sm border
                    {{ request('category') === $cat->slug ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                >
                    {{ $cat->name }}
                </a>
            @endforeach

            <form method="GET" class="ml-auto flex items-center gap-2">
                @if (request('category'))
                    <input
                        type="hidden"
                        name="category"
                        value="{{ request('category') }}"
                    />
                @endif
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tìm bài viết..."
                    class="rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                />
                <button
                    type="submit"
                    class="px-3 py-1.5 rounded bg-gray-800 text-white text-sm hover:bg-gray-900"
                >
                    Tìm
                </button>
            </form>
        </div>

        {{-- Grid --}}
        @if ($posts->isEmpty())
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <p class="text-gray-500">Không có bài viết nào.</p>
                @if (request()->hasAny(['category', 'q']))
                    <a
                        href="{{ route('blog.index') }}"
                        class="text-indigo-600 hover:underline text-sm mt-2 inline-block"
                    >
                        Xoá bộ lọc
                    </a>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($posts as $post)
                    @include ('shop.blog._post_card', ['post' => $post])
                @endforeach
            </div>

            @if ($posts->hasPages())
                <div>{{ $posts->links() }}</div>
            @endif
        @endif
    </div>
@endsection
