{{-- Thẻ sản phẩm dùng trong cửa hàng. --}}
@php
$buyNowVariants = $product->variants->map(fn ($variant) => [
'id' => (int) $variant->id,
'name' => $variant->display_name,
'price' => number_format($variant->final_price).'đ',
'stock' => (int) $variant->stock,
'options' => $variant->attributeValues->map(fn ($value) => [
'groupId' => (int) $value->attribute_id,
'groupName' => $value->attribute->name,
'valueId' => (int) $value->id,
'label' => $value->value,
])->values()->all(),
])->values();
$variantCount = (int) ($product->variants_count ?? $buyNowVariants->count());
$activeVariantCount = (int) ($product->active_variants_count ?? $buyNowVariants->count());
$hasUnavailableVariants = $variantCount > 0 && $activeVariantCount === 0;
$ratingAverage = min(5, max(0, (float) $product->rating_avg));
$ratingCount = (int) $product->rating_count;
$displayRating = round($ratingAverage * 2) / 2;
@endphp

<article x-data="{
        variants: @js($buyNowVariants),
        selectedVariantId: null,
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
        },
        get selectedVariant() {
            return this.variants.find(variant => variant.id === this.selectedVariantId) ?? null;
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
            return this.variants.some(variant => this.matches(variant, choices));
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
            this.selectedVariantId = this.variants.find(variant => variant.id === id)?.id ?? null;
        },
    }" class="relative flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('products.show', $product->slug) }}" class="flex flex-1 flex-col">
        <div class="aspect-square overflow-hidden bg-gray-100">
            <img src="{{ $product->thumbnail ?? asset('images/product-placeholder.svg') }}"
                onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}';"
                alt="{{ $product->name }}" class="h-full w-full object-cover" />
        </div>
        <div class="flex flex-1 flex-col p-3">
            <div class="mb-1 text-xs text-gray-500">
                {{ $product->brand?->name }}
            </div>
            <h3 class="flex-1 text-sm font-medium line-clamp-2">
                {{ $product->name }}
            </h3>
            <div class="mt-1 flex items-center gap-1.5 text-xs">
                <span role="img"
                    aria-label="{{ $ratingCount > 0 ? 'Đánh giá '.number_format($ratingAverage, 1).' trên 5 sao' : 'Chưa có đánh giá' }}"
                    class="flex items-center gap-0.5">
                    @for ($star = 1; $star <= 5; $star++) <span class="relative inline-block text-gray-300"
                        aria-hidden="true">
                        ★
                        @if ($ratingCount > 0 && $displayRating >= $star - 0.5)
                        <span
                            class="absolute inset-y-0 left-0 overflow-hidden text-amber-400 {{ $displayRating >= $star ? 'w-full' : 'w-1/2' }}">★</span>
                        @endif
                </span>
                @endfor
                </span>
                @if ($ratingCount > 0)
                <span class="font-medium text-gray-700">{{ number_format($ratingAverage, 1) }}</span>
                <span class="text-gray-500">({{ $ratingCount }})</span>
                @else
                <span class="text-gray-400">Chưa có đánh giá</span>
                @endif
                <span class="text-gray-300">·</span>
                <span class="truncate text-gray-500">Đã bán {{ number_format((int) $product->sold_count, 0, ',', '.') }}</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between gap-2">
                @if ($product->is_on_sale)
                <span class="text-xs text-gray-400 line-through whitespace-nowrap">
                    {{ number_format($product->price) }}đ
                </span>
                @endif
                <span
                    class="ml-auto font-semibold whitespace-nowrap {{ $product->is_on_sale ? 'text-red-600' : 'text-gray-900' }}">
                    {{ number_format($product->is_on_sale ? $product->sale_price : $product->price) }}đ
                </span>
            </div>
        </div>
    </a>

    <div class="p-3 pt-0">
        @if ($hasUnavailableVariants)
        <button type="button" disabled
            class="w-full cursor-not-allowed rounded-lg bg-gray-300 px-4 py-2 text-sm font-semibold text-gray-600">
            Hết phân loại khả dụng
        </button>
        @elseif ($buyNowVariants->count() > 1)
        <button type="button"
            x-on:click="selectedVariantId = null; chosen = {}; $dispatch('open-modal', 'buy-now-modal-{{ $product->id }}')"
            class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            Mua ngay
        </button>

        <x-modal name="buy-now-modal-{{ $product->id }}" maxWidth="lg" focusable>
            <div role="dialog" aria-modal="true" aria-labelledby="quick-view-title-{{ $product->id }}" class="p-5">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="quick-view-title-{{ $product->id }}" class="text-lg font-bold">
                            Chọn phân loại
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">{{ $product->name }}</p>
                    </div>
                    <button type="button" x-on:click="$dispatch('close-modal', 'buy-now-modal-{{ $product->id }}')"
                        aria-label="Đóng" class="rounded p-1 text-2xl leading-none text-gray-500 hover:bg-gray-100">
                        &times;
                    </button>
                </div>

                <form method="POST" action="{{ route('checkout.buy-now') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}" />
                    <input type="hidden" name="quantity" value="1" />
                    <input type="hidden" name="variant_id" x-bind:value="selectedVariantId" />

                    <template x-if="useGroups">
                        <div class="space-y-4">
                            <template x-for="group in groups" :key="group.id">
                                <fieldset>
                                    <legend class="mb-2 text-sm font-medium text-gray-800"
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
                            <legend class="mb-2 text-sm font-medium">Phân loại sản phẩm</legend>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <template x-for="variant in variants" :key="variant.id">
                                    <button type="button" x-on:click="selectVariant(variant.id)"
                                        x-bind:aria-pressed="selectedVariantId === variant.id"
                                        x-bind:class="selectedVariantId === variant.id
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                        : 'border-gray-300 text-gray-700 hover:border-indigo-500'"
                                        class="flex items-start justify-between gap-3 rounded-lg border p-3 text-left">
                                        <span>
                                            <span class="block text-sm font-medium" x-text="variant.name"></span>
                                            <span class="mt-1 block text-xs text-gray-500"
                                                x-text="variant.stock > 0 ? 'Còn hàng: ' + variant.stock : 'Hết hàng'"></span>
                                        </span>
                                        <span class="whitespace-nowrap text-sm font-semibold" x-text="variant.price"></span>
                                    </button>
                                </template>
                            </div>
                        </fieldset>
                    </template>

                    <p x-show="selectedVariantId !== null && selectedVariant?.stock < 1" class="text-sm text-amber-700">
                        Phân loại này đã hết hàng, vui lòng chọn phân loại còn hàng.
                    </p>

                    <p x-show="selectedVariantId === null" class="text-sm text-amber-700">
                        Vui lòng chọn phân loại trước khi tiếp tục.
                    </p>

                    <button type="submit"
                        x-bind:disabled="selectedVariantId === null || selectedVariant.stock < 1"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-gray-300">
                        Tiếp tục thanh toán
                    </button>
                </form>
            </div>
        </x-modal>
        @else
        @if ($buyNowVariants->isNotEmpty() && $buyNowVariants->first()['stock'] < 1)
        <button type="button" disabled
            class="w-full cursor-not-allowed rounded-lg bg-gray-300 px-4 py-2 text-sm font-semibold text-gray-600">
            Hết hàng
        </button>
        @else
        <form method="POST" action="{{ route('checkout.buy-now') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}" />
            <input type="hidden" name="quantity" value="1" />
            @if ($buyNowVariants->isNotEmpty())
            <input type="hidden" name="variant_id" value="{{ $buyNowVariants->first()['id'] }}" />
            @endif
            <button type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Mua ngay
            </button>
        </form>
        @endif
        @endif
    </div>
</article>