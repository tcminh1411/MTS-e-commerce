{{-- Chi tiết bài viết blog. --}}
@extends ('layouts.storefront')

@section ('title', $post->meta_title ?: $post->title)

@section ('content')
    <article class="max-w-3xl mx-auto">
        {{-- Breadcrumb --}}
        <nav class="text-xs text-gray-500 mb-4">
            <a href="{{ route('home') }}" class="hover:text-indigo-600"
                >Trang chủ</a
            >
            <span class="mx-1">/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-indigo-600"
                >Blog</a
            >
            @if ($post->category)
                <span class="mx-1">/</span>
                <a
                    href="{{ route('blog.index', ['category' => $post->category->slug]) }}"
                    class="hover:text-indigo-600"
                    >{{ $post->category->name }}</a
                >
            @endif
        </nav>

        {{-- Header --}}
        <header class="mb-6">
            <h1
                class="text-2xl md:text-3xl font-bold text-gray-800 leading-tight"
            >
                {{ $post->title }}
            </h1>
            <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                <span>{{ $post->published_at?->format('d/m/Y') }}</span>
                <span>•</span>
                <span>{{ number_format($post->view_count) }} lượt xem</span>
            </div>
        </header>

        {{-- Thumbnail --}}
        @if ($post->thumbnail)
            <div class="mb-6 rounded-lg overflow-hidden">
                <img
                    src="{{ $post->thumbnail_url }}"
                    alt="{{ $post->title }}"
                    class="w-full h-auto object-cover"
                />
            </div>
        @endif

        {{-- Excerpt --}}
        @if ($post->excerpt)
            <p class="text-base text-gray-600 italic border-l-4 border-indigo-200 pl-4 mb-6">
                {{ $post->excerpt }}
            </p>
        @endif

        {{-- Body --}}
        <div
            class="prose prose-slate max-w-none prose-headings:text-gray-800 prose-headings:font-semibold prose-h2:text-xl prose-h2:mt-6 prose-h2:mb-3 prose-h3:text-base prose-h3:mt-5 prose-h3:mb-2 prose-p:text-gray-700 prose-p:leading-relaxed prose-ul:my-3 prose-li:text-gray-700 prose-li:my-1 prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline prose-strong:text-gray-900"
        >
            {!! $post->body !!}
        </div>

        {{-- Related --}}
        @if ($related->isNotEmpty())
            <section class="mt-12 pt-6 border-t">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Bài viết liên quan
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach ($related as $post)
                        @include ('shop.blog._post_card', ['post' => $post])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Back --}}
        <div class="mt-10 pt-4 border-t">
            <a
                href="{{ route('blog.index') }}"
                class="text-sm text-indigo-600 hover:underline"
            >
                ← Về danh sách blog
            </a>
        </div>
    </article>
@endsection
