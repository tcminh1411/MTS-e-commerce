{{-- Thẻ sản phẩm dùng trong cửa hàng. --}}
<a
    href="{{ route('products.show', $product->slug) }}"
    class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden flex flex-col"
>
    <div class="aspect-square bg-gray-100 overflow-hidden">
        @if ($product->thumbnail)
            <img
                src="{{ $product->thumbnail }}"
                class="w-full h-full object-cover"
            />
        @else
            <div
                class="w-full h-full flex items-center justify-center text-gray-400 text-sm"
            >
                No image
            </div>
        @endif
    </div>
    <div class="p-3 flex-1 flex flex-col">
        <div class="text-xs text-gray-500 mb-1">
            {{ $product->brand?->name }}
        </div>
        <h3 class="font-medium text-sm line-clamp-2 flex-1">
            {{ $product->name }}
        </h3>
        <div class="mt-2">
            @if ($product->is_on_sale)
                <div class="text-xs line-through text-gray-400">
                    {{ number_format($product->price) }}đ
                </div>
                <div class="text-red-600 font-semibold">
                    {{ number_format($product->sale_price) }}đ
                </div>
            @else
                <div class="font-semibold">
                    {{ number_format($product->price) }}đ
                </div>
            @endif
        </div>
    </div>
</a>
