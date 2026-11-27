{{-- Mẫu giao diện chi tiết sản phẩm phía cửa hàng. --}}
@extends ('layouts.storefront')
@section ('title', $product->name)

@section ('content')
<nav class="text-sm text-gray-500 mb-4">
    <a href="{{ route('home') }}" class="hover:underline">Trang chủ</a>
    @if ($product->category)
    /
    <a href="{{ route('products.index', ['category' => $product->category->slug]) }}"
        class="hover:underline">{{ $product->category->name }}</a>
    @endif
    / <span class="text-gray-700">{{ $product->name }}</span>
</nav>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10" x-data="{
            variants: @js($variantOptions),
            selectedVariantId: @js($selectedVariantId),
            quantity: 1,
            mainImage: @js($initialImage ?: asset('images/product-placeholder.svg')),
            defaultImage: @js($product->images->firstWhere('is_primary', true)?->url ?? $product->images->first()?->url ?? asset('images/product-placeholder.svg')),

chosen: {},
groups: [],
useGroups: false,

init() {
    const groups = new Map();

    for (const variant of this.variants) {
        for (const option of variant.options) {
            if (!groups.has(option.groupId)) {
                groups.set(option.groupId, {
                    id: option.groupId,
                    name: option.groupName,
                    values: new Map(),
                });
            }

            groups.get(option.groupId).values.set(option.valueId, {
                id: option.valueId,
                label: option.label,
            });
        }
    }

    this.groups = Array.from(groups.values(), group => ({
        id: group.id,
        name: group.name,
        values: Array.from(group.values.values()),
    }));

    // Dữ liệu cũ thiếu thuộc tính hoặc có hai SKU cùng tổ hợp:
    // cho khách chọn trực tiếp theo tên biến thể.
    const combinations = this.variants.map(variant =>
        variant.options
            .map(option => `${option.groupId}:${option.valueId}`)
            .sort()
            .join('|')
    );

    this.useGroups = this.groups.length > 0
        && this.variants.every(variant =>
            variant.options.length === this.groups.length
            && new Set(variant.options.map(option => option.groupId)).size === this.groups.length
        )
        && new Set(combinations).size === this.variants.length;

    if (this.selectedVariant) {
        this.chosen = Object.fromEntries(
            this.selectedVariant.options.map(option => [option.groupId, option.valueId])
        );
    }
},

get selectedVariant() {
    return this.variants.find(variant => variant.id === this.selectedVariantId) ?? null;
},

get canAddToCart() {
    return @js(! $hasVariants) || this.selectedVariant !== null;
},

get canBuyNow() {
    return @js(! $hasVariants)
        || (this.selectedVariant !== null && this.selectedVariant.stock >= this.quantity);
},

matches(variant, choices) {
    return Object.entries(choices).every(([groupId, valueId]) =>
        variant.options.some(option =>
            option.groupId === Number(groupId) && option.valueId === valueId
        )
    );
},

canPick(groupId, valueId) {
    const choices = { ...this.chosen, [groupId]: valueId };
    return this.variants.some(variant =>
        this.matches(variant, choices)
    );
},

pick(groupId, valueId) {
    const choices = { ...this.chosen };

    if (choices[groupId] === valueId) {
        delete choices[groupId];
    } else {
        if (!this.canPick(groupId, valueId)) return;
        choices[groupId] = valueId;
    }

    this.chosen = choices;

    const complete = Object.keys(choices).length === this.groups.length;
    const variant = complete
        ? this.variants.find(item => this.matches(item, choices))
        : null;

    this.selectVariant(variant?.id ?? null);
},

selectVariant(id) {
    const variant = this.variants.find(item => item.id === id);
    this.selectedVariantId = variant?.id ?? null;
    this.mainImage = variant?.image || this.defaultImage;

    if (variant) {
        this.quantity = Math.max(1, Math.min(Number(this.quantity) || 1, 99));
    }
},

            selectImage(event) {
                this.mainImage = event.currentTarget.dataset.image;
            },
        }">
    <div>
        <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden mb-3">
            <img id="mainImg" src="{{ $initialImage ?: asset('images/product-placeholder.svg') }}"
                x-bind:src="mainImage"
                onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}';"
                alt="{{ $product->name }}" class="w-full h-full object-cover" />
        </div>

        @if ($product->images->count() > 1)
        <div class="grid grid-cols-5 gap-2">
            @foreach ($product->images as $img)
            <button type="button" data-image="{{ $img->url }}" x-on:click="selectImage($event)"
                class="aspect-square rounded border overflow-hidden hover:border-indigo-500">
                <img src="{{ $img->url }}"
                    onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}';"
                    alt="{{ $product->name }}" class="w-full h-full object-cover" />
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
            <span>⭐ {{ number_format($product->rating_avg, 1) }} ({{ $product->rating_count }})</span>
            <span>· Đã bán {{ $product->sold_count }}</span>
        </div>

        <div class="bg-gray-50 rounded-xl p-4 mb-5">
            <div class="text-sm text-gray-400 line-through"
                x-show="selectedVariant ? selectedVariant.isOnSale : @js($product->is_on_sale)"
                x-text="selectedVariant ? selectedVariant.originalPriceLabel : @js(number_format($product->price).'đ')">
                {{ number_format($selectedVariant['price'] ?? $product->price) }}đ
            </div>
            <div class="text-3xl font-bold text-red-600"
                x-text="selectedVariant ? (selectedVariant.salePriceLabel ?? selectedVariant.priceLabel) : @js(number_format($product->sale_price ?? $product->price).'đ')">
                {{ number_format($selectedVariant['salePrice'] ?? $selectedVariant['price'] ?? $product->sale_price ?? $product->price) }}đ
            </div>
        </div>

        <p class="text-sm text-gray-700 mb-5">{{ $product->short_description }}</p>

        <div class="flex gap-3 mb-5">
            <form method="POST" action="{{ route('cart.store') }}" class="flex flex-wrap gap-3 mb-5">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}" />
                @if ($variantOptions->isNotEmpty())
                <input type="hidden" name="variant_id" x-bind:value="selectedVariantId" />
                @endif

                @if ($variantOptions->isNotEmpty())
                <div class="w-full space-y-4 mb-2">
                    <template x-if="useGroups">
                        <div class="space-y-4">
                            <template x-for="group in groups" :key="group.id">
                                <fieldset>
                                    <legend class="mb-2 text-sm font-semibold text-gray-800"
                                        x-text="group.name"></legend>

                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="value in group.values" :key="value.id">
                                            <button type="button" x-on:click="pick(group.id, value.id)"
                                                x-bind:disabled="!canPick(group.id, value.id)"
                                                x-bind:aria-pressed="chosen[group.id] === value.id"
                                                x-bind:class="chosen[group.id] === value.id
                                                ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                                : 'border-gray-300 bg-white text-gray-700'"
                                                class="rounded-lg border px-4 py-2 text-sm font-medium
                                                enabled:hover:border-indigo-500
                                                disabled:cursor-not-allowed disabled:opacity-40"
                                                x-text="value.label"></button>
                                        </template>
                                    </div>
                                </fieldset>
                            </template>
                        </div>
                    </template>

                    <template x-if="!useGroups">
                        <fieldset>
                            <legend class="mb-2 text-sm font-semibold">Phân loại</legend>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="variant in variants" :key="variant.id">
                                    <button type="button" x-on:click="selectVariant(variant.id)"
                                        x-bind:aria-pressed="selectedVariantId === variant.id"
                                        x-bind:class="selectedVariantId === variant.id
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                        : 'border-gray-300 text-gray-700'"
                                        class="rounded-lg border px-4 py-2 text-sm
                                        disabled:cursor-not-allowed disabled:opacity-40"
                                        x-text="variant.name"></button>
                                </template>
                            </div>
                        </fieldset>
                    </template>
                </div>
                @endif

                <input type="number" name="quantity" value="1" min="1" max="99" x-model.number="quantity"
                    x-bind:max="99"
                    x-bind:disabled="!canAddToCart" class="w-20 border rounded-lg px-3 py-3 text-sm text-center" />
                <button type="submit" x-bind:disabled="!canAddToCart"
                    class="flex-1 px-5 py-3 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700">
                    Thêm vào giỏ
                </button>
                <button type="submit" formaction="{{ route('checkout.buy-now') }}" x-bind:disabled="!canBuyNow"
                    class="flex-1 px-5 py-3 rounded-lg bg-orange-500 text-white font-medium hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-gray-300">
                    Mua ngay
                </button>
            </form>
            @auth
            @php
            $inWishlist = \App\Models\Wishlist::query()
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->where('product_id', $product->id)
            ->exists();
            @endphp
            <form method="POST" action="{{ route('wishlist.toggle', $product) }}">
                @csrf
                <button type="submit" title="{{ $inWishlist ? 'Xoá khỏi yêu thích' : 'Thêm vào yêu thích' }}"
                    class="px-5 py-3 rounded-lg border hover:bg-gray-50 {{ $inWishlist ? 'text-red-500 border-red-300 bg-red-50' : '' }}">
                    {{ $inWishlist ? '♥' : '♡' }}
                </button>
            </form>
            @else
            <a href="{{ route('login') }}" title="Đăng nhập để yêu thích"
                class="px-5 py-3 rounded-lg border hover:bg-gray-50">♡</a>
            @endauth
        </div>

        <div class="text-sm text-gray-500 space-y-1">
            <div x-show="selectedVariant ? selectedVariant.weightGram !== null : @js($product->weight_gram !== null)">
                Cân nặng:
                <span
                    x-text="selectedVariant ? selectedVariant.weightGram : @js($product->weight_gram)">{{ $selectedVariant['weightGram'] ?? $product->weight_gram }}</span>g
            </div>
            <div x-show="selectedVariant ? Boolean(selectedVariant.sku) : @js((bool) $product->sku)">
                Mã SP: <span
                    x-text="selectedVariant ? selectedVariant.sku : @js($product->sku)">{{ $selectedVariant['sku'] ?? $product->sku }}</span>
            </div>
            @if ($hasVariants)
            <div aria-live="polite" x-text="selectedVariant
            ? (selectedVariant.stock > 0 ? 'Còn hàng: ' + selectedVariant.stock : 'Hết hàng')
            : variants.some(variant => variant.stock > 0)
                ? 'Vui lòng chọn phân loại sản phẩm'
                : 'Hết hàng'"></div>
            @endif
        </div>
        <div class="mt-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm font-medium text-blue-800 mb-2">📩 Nhận thông báo khi có hàng / giảm giá</p>
            <form method="POST" action="{{ route('stock-alerts.store', $product) }}" class="flex gap-2">
                @csrf
                <input type="email" name="email" value="{{ \Illuminate\Support\Facades\Auth::user()?->email }}"
                    placeholder="email@example.com" required class="flex-1 px-3 py-2 border rounded-lg text-sm" />
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 whitespace-nowrap">
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
            <span class="font-semibold text-gray-900">{{ number_format($product->rating_avg, 1) }}</span>
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
                <span class="font-medium text-gray-900">{{ $rv->user?->full_name ?? 'Ẩn danh' }}</span>
                <span class="text-yellow-500">{{ $rv->stars }}</span>
                <span class="text-xs text-gray-400">{{ $rv->created_at->format('d/m/Y') }}</span>
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