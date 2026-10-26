{{-- Trang chủ cửa hàng. --}}
@extends ('layouts.storefront')
@section ('title', 'Trang chủ')

@section ('content')
    <section
        class="rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-10 mb-10"
    >
        <h1 class="text-3xl md:text-4xl font-bold mb-2">Chào mừng đến MTS</h1>
        <p class="opacity-90">Mua sắm công nghệ chính hãng, giao nhanh toàn quốc</p>
        <a
            href="{{ route('products.index') }}"
            class="inline-block mt-5 px-5 py-2.5 bg-white text-indigo-700 font-medium rounded-lg"
        >
            Xem sản phẩm →
        </a>
    </section>

    {{-- ===== BANNER TOP ===== --}}
    @if ($bannersTop->count())
        <section class="mb-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($bannersTop as $b)
                    <a
                        href="{{ $b->link ?: '#' }}"
                        class="block rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition {{ $bannersTop->count() === 1 ? 'md:col-span-2' : '' }}"
                    >
                        <img
                            src="{{ $b->image_url }}"
                            alt="{{ $b->title }}"
                            class="w-full h-40 md:h-52 object-cover"
                        />
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mb-10">
        <h2 class="text-xl font-bold mb-4">Danh mục</h2>
        <div class="grid grid-cols-6 gap-3">
            @foreach ($categories as $c)
                <a
                    href="{{ route('products.index', ['category' => $c->slug]) }}"
                    class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition group"
                >
                    <div class="aspect-[4/3] bg-gray-100 overflow-hidden">
                        @if ($c->image_url_full)
                            <img
                                src="{{ $c->image_url_full }}"
                                alt="{{ $c->name }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                            />
                        @else
                            <div
                                class="w-full h-full flex items-center justify-center text-gray-300 text-xs"
                            >
                                No image
                            </div>
                        @endif
                    </div>
                    <div class="p-2 text-center">
                        <div class="font-medium text-xs">{{ $c->name }}</div>
                        <div class="text-[10px] text-gray-500 mt-0.5">
                            {{ $c->children->count() }} danh mục con
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    @if ($featured->count())
        <section class="mb-10">
            <h2 class="text-xl font-bold mb-4">Sản phẩm nổi bật</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ($featured as $product)
                    @include ('shop.products._card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== BANNER MIDDLE ===== --}}
    @if ($bannersMiddle->count())
        <section class="mb-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($bannersMiddle as $b)
                    <a
                        href="{{ $b->link ?: '#' }}"
                        class="block rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition {{ $bannersMiddle->count() === 1 ? 'md:col-span-2' : '' }}"
                    >
                        <img
                            src="{{ $b->image_url }}"
                            alt="{{ $b->title }}"
                            class="w-full h-40 md:h-52 object-cover"
                        />
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($newest->count())
        <section>
            <h2 class="text-xl font-bold mb-4">Sản phẩm mới</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ($newest as $product)
                    @include ('shop.products._card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($bannersBottom->count())
        <section class="mt-10 mb-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($bannersBottom as $b)
                    <a
                        href="{{ $b->link ?: '#' }}"
                        class="block rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition {{ $bannersBottom->count() === 1 ? 'md:col-span-2' : '' }}"
                    >
                        <img
                            src="{{ $b->image_url }}"
                            alt="{{ $b->title }}"
                            class="w-full h-40 md:h-52 object-cover"
                        />
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
