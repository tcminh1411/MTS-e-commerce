{{-- Mẫu giao diện chi tiết trang tĩnh phía cửa hàng. --}}
@extends ('layouts.storefront')

@section ('title', $page->meta_title ?: $page->title)

@section ('content')
    <article class="max-w-3xl mx-auto">
        <nav class="text-xs text-gray-500 mb-4">
            <a href="{{ route('home') }}" class="hover:text-indigo-600"
                >Trang chủ</a
            >
            <span class="mx-1">/</span>
            <span class="text-gray-700">{{ $page->title }}</span>
        </nav>

        <header class="mb-6 pb-4 border-b">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                {{ $page->title }}
            </h1>
            <p class="text-xs text-gray-400 mt-1">
                Cập nhật: {{ $page->updated_at->format('d/m/Y') }}
            </p>
        </header>

        <div
            class="prose prose-slate max-w-none prose-headings:text-gray-800 prose-headings:font-semibold prose-h2:text-xl prose-h2:mt-6 prose-h2:mb-3 prose-h3:text-base prose-h3:mt-5 prose-h3:mb-2 prose-p:text-gray-700 prose-p:leading-relaxed prose-ul:my-3 prose-li:text-gray-700 prose-li:my-1 prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline prose-strong:text-gray-900"
        >
            {!! $page->body !!}
        </div>

        <div class="mt-10 pt-4 border-t">
            <a
                href="{{ route('home') }}"
                class="text-sm text-indigo-600 hover:underline"
            >
                ← Về trang chủ
            </a>
        </div>
    </article>
@endsection
