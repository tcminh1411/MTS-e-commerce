{{-- Thẻ bài viết trong trang blog. --}}
<article
    class="bg-white rounded-lg shadow hover:shadow-md transition overflow-hidden flex flex-col"
>
    <a
        href="{{ route('blog.show', $post) }}"
        class="block aspect-video bg-gray-100 overflow-hidden"
    >
        @if ($post->thumbnail)
            <img
                src="{{ $post->thumbnail_url }}"
                alt="{{ $post->title }}"
                class="w-full h-full object-cover hover:scale-105 transition duration-300"
            />
        @else
            <div
                class="w-full h-full flex items-center justify-center text-gray-300"
            >
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
            </div>
        @endif
    </a>

    <div class="p-4 flex-1 flex flex-col">
        @if ($post->category)
            <a
                href="{{ route('blog.index', ['category' => $post->category->slug]) }}"
                class="text-xs text-indigo-600 hover:underline mb-1.5"
            >
                {{ $post->category->name }}
            </a>
        @endif

        <h3 class="font-semibold text-gray-800 leading-snug mb-2 line-clamp-2">
            <a
                href="{{ route('blog.show', $post) }}"
                class="hover:text-indigo-600"
            >
                {{ $post->title }}
            </a>
        </h3>

        @if ($post->excerpt)
            <p class="text-sm text-gray-600 line-clamp-3 flex-1">
                {{ $post->excerpt }}
            </p>
        @endif

        <div
            class="mt-3 pt-3 border-t flex items-center justify-between text-xs text-gray-400"
        >
            <span>{{ $post->published_at?->format('d/m/Y') }}</span>
            <span>{{ number_format($post->view_count) }} lượt xem</span>
        </div>
    </div>
</article>
