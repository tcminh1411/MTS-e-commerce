{{-- Mẫu giao diện chi tiết sản phẩm phía cửa hàng. --}}
@extends ('layouts.storefront')
@section ('title', $product->name)

@section ('content')
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('home') }}" class="hover:underline">Trang chủ</a>
        @if ($product->category)
            /
            <a
                href="{{ route('products.index', ['category' => $product->category->slug]) }}"
                class="hover:underline"
                >{{ $product->category->name }}</a
            >
        @endif
        / <span class="text-gray-700">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
        <div>
            @php $main = $product->images->firstWhere('is_primary', true) ?? $product->images->first(); @endphp
            <div
                class="aspect-square bg-gray-100 rounded-xl overflow-hidden mb-3"
            >
                @if ($main)
                    <img
                        id="mainImg"
                        src="{{ $main->url }}"
                        class="w-full h-full object-cover"
                    />
                @else
                    <div
                        class="w-full h-full flex items-center justify-center text-gray-400"
                    >
                        No image
                    </div>
                @endif
            </div>

            @if ($product->images->count() > 1)
                <div class="grid grid-cols-5 gap-2">
                    @foreach ($product->images as $img)
                        <button
                            type="button"
                            onclick="document.getElementById('mainImg').src='{{ $img->url }}'"
                            class="aspect-square rounded border overflow-hidden hover:border-indigo-500"
                        >
                            <img
                                src="{{ $img->url }}"
                                class="w-full h-full object-cover"
                            />
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <div class="text-sm text-gray-500 mb-1">
                {{ $product->brand?->name }}
            </div>
            <h1 class="text-2xl font-bold mb-3">{{ $product->name }}</h1>

            <div class="flex items-center gap-3 mb-4 text-sm text-gray-500">
                <span
                    >⭐ {{ number_format($product->rating_avg, 1) }} ({{ $product->rating_count }})</span
                >
                <span>· Đã bán {{ $product->sold_count }}</span>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-5">
                @if ($product->is_on_sale)
                    <div class="text-sm text-gray-400 line-through">
                        {{ number_format($product->price) }}đ
                    </div>
                    <div class="text-3xl font-bold text-red-600">
                        {{ number_format($product->sale_price) }}đ
                    </div>
                @else
                    <div class="text-3xl font-bold text-red-600">
                        {{ number_format($product->price) }}đ
                    </div>
                @endif
            </div>

            <p class="text-sm text-gray-700 mb-5">{{ $product->short_description }}</p>

            @if ($product->variants->count())
                <div class="mb-5">
                    <div class="text-sm font-medium mb-2">Phân loại</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($product->variants as $v)
                            <button
                                type="button"
                                class="px-3 py-1.5 border rounded-lg text-sm hover:border-indigo-500"
                            >
                                {{ $v->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex gap-3 mb-5">
                <form
                    method="POST"
                    action="{{ route('cart.store') }}"
                    class="flex gap-3 mb-5"
                >
                    @csrf
                    <input
                        type="hidden"
                        name="product_id"
                        value="{{ $product->id }}"
                    />
                    @if ($product->variants->count())
                        <select
                            name="variant_id"
                            class="border rounded-lg px-3 py-3 text-sm"
                        >
                            @foreach ($product->variants as $v)
                                <option value="{{ $v->id }}">
                                    {{ $v->name }} — {{ number_format($v->final_price) }}đ
                                </option>
                            @endforeach
                        </select>
                    @endif
                    <input
                        type="number"
                        name="quantity"
                        value="1"
                        min="1"
                        max="99"
                        class="w-20 border rounded-lg px-3 py-3 text-sm text-center"
                    />
                    <button
                        class="flex-1 px-5 py-3 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700"
                    >
                        Thêm vào giỏ
                    </button>
                </form>
                @auth
                    @php
            $inWishlist = \App\Models\Wishlist::query()
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->where('product_id', $product->id)
            ->exists();
            @endphp
                    <form
                        method="POST"
                        action="{{ route('wishlist.toggle', $product) }}"
                    >
                        @csrf
                        <button
                            type="submit"
                            title="{{ $inWishlist ? 'Xoá khỏi yêu thích' : 'Thêm vào yêu thích' }}"
                            class="px-5 py-3 rounded-lg border hover:bg-gray-50 {{ $inWishlist ? 'text-red-500 border-red-300 bg-red-50' : '' }}"
                        >
                            {{ $inWishlist ? '♥' : '♡' }}
                        </button>
                    </form>
                @else
                    <a
                        href="{{ route('login') }}"
                        title="Đăng nhập để yêu thích"
                        class="px-5 py-3 rounded-lg border hover:bg-gray-50"
                        >♡</a
                    >
                @endauth
            </div>

            <div class="text-sm text-gray-500 space-y-1">
                @if ($product->weight_gram)
                    <div>Cân nặng: {{ $product->weight_gram }}g</div>
                @endif
                @if ($product->sku)
                    <div>Mã SP: {{ $product->sku }}</div>
                @endif
            </div>
            <div class="mt-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm font-medium text-blue-800 mb-2">📩 Nhận thông báo khi có hàng / giảm giá</p>
                <form
                    method="POST"
                    action="{{ route('stock-alerts.store', $product) }}"
                    class="flex gap-2"
                >
                    @csrf
                    <input
                        type="email"
                        name="email"
                        value="{{ \Illuminate\Support\Facades\Auth::user()?->email }}"
                        placeholder="email@example.com"
                        required
                        class="flex-1 px-3 py-2 border rounded-lg text-sm"
                    />
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 whitespace-nowrap"
                    >
                        Đăng ký
                    </button>
                </form>
                @error ('email')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- ===== REVIEWS ===== --}}
    <div id="reviewsSection" class="bg-white rounded-xl p-6 shadow-sm mb-10">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 class="text-lg font-bold">Đánh giá sản phẩm</h2>
            @if ($product->rating_count > 0)
                <div class="text-sm text-gray-600">
                    <span class="text-yellow-500 text-lg">★</span>
                    <span
                        class="font-semibold text-gray-900"
                        >{{ number_format($product->rating_avg, 1) }}</span
                    >
                    / 5 · {{ $product->rating_count }} đánh giá
                </div>
            @endif
        </div>

        @if ($product->reviews->isEmpty())
            <p class="text-sm text-gray-500">Chưa có đánh giá nào cho sản phẩm này.</p>
        @else
            <div class="space-y-4">
                @foreach ($product->reviews as $rv)
                    <div class="border-b last:border-0 pb-4 last:pb-0">
                        <div class="flex flex-wrap items-center gap-3 mb-1">
                            <span
                                class="font-medium text-gray-900"
                                >{{ $rv->user?->full_name ?? 'Ẩn danh' }}</span
                            >
                            <span
                                class="text-yellow-500"
                                >{{ $rv->stars }}</span
                            >
                            <span
                                class="text-xs text-gray-400"
                                >{{ $rv->created_at->format('d/m/Y') }}</span
                            >
                        </div>
                        @if ($rv->comment)
                            <p class="text-sm text-gray-700">{{ $rv->comment }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl p-6 shadow-sm mb-10">
        <h2 class="text-lg font-bold mb-3">Mô tả sản phẩm</h2>
        <div class="prose max-w-none text-sm text-gray-700">
            {!! $product->description !!}
        </div>
    </div>

    @if ($related->count())
        <section>
            <h2 class="text-xl font-bold mb-4">Sản phẩm liên quan</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ($related as $product)
                    @include ('shop.products._card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif
@endsection
