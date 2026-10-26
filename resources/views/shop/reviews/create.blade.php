{{-- Mẫu giao diện tạo mới đánh giá phía cửa hàng. --}}
@extends ('layouts.storefront')

@section ('title', 'Viết đánh giá')

@section ('content')
    <div class="max-w-2xl mx-auto py-8 px-4">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500 mb-4">
            <a href="{{ route('orders.show', $order) }}" class="hover:underline"
                >Đơn {{ $order->order_number }}</a
            >
            <span class="mx-1">/</span>
            <span>Viết đánh giá</span>
        </nav>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h1 class="text-xl font-bold mb-1">Viết đánh giá sản phẩm</h1>
            <p class="text-sm text-gray-500 mb-5">Đơn hàng: <span class="font-mono">{{ $order->order_number }}</span></p>

            {{-- Sản phẩm --}}
            <div class="border rounded-lg p-4 bg-gray-50 mb-6">
                <div class="font-semibold text-gray-900">
                    {{ $product->name }}
                </div>
                @if ($item->variant_name)
                    <div class="text-sm text-gray-600">
                        Phân loại: {{ $item->variant_name }}
                    </div>
                @endif
                @if ($item->sku)
                    <div class="text-xs text-gray-500">
                        SKU: {{ $item->sku }}
                    </div>
                @endif
            </div>

            {{-- Form --}}
            <form
                method="POST"
                action="{{ route('orders.reviews.store', $order) }}"
            >
                @csrf
                <input
                    type="hidden"
                    name="product_id"
                    value="{{ $product->id }}"
                />

                {{-- Rating --}}
                <div class="mb-5">
                    <label class="block font-medium mb-2"
                        >Đánh giá của bạn
                        <span class="text-red-500">*</span></label
                    >
                    <div
                        class="flex flex-row-reverse justify-end gap-1 text-3xl"
                    >
                        @for ($i = 5; $i >= 1; $i--)
                            <input
                                type="radio"
                                id="star{{ $i }}"
                                name="rating"
                                value="{{ $i }}"
                                class="peer hidden"
                                {{ old('rating') == $i ? 'checked' : '' }}
                            />
                            <label
                                for="star{{ $i }}"
                                class="cursor-pointer text-gray-300 peer-checked:text-yellow-400 hover:text-yellow-400 [&:has(~label:hover)]:text-yellow-400"
                            >
                                ★
                            </label>
                        @endfor
                    </div>
                    @error ('rating')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Comment --}}
                <div class="mb-5">
                    <label for="comment" class="block font-medium mb-2"
                        >Nhận xét (tùy chọn)</label
                    >
                    <textarea
                        id="comment"
                        name="comment"
                        rows="5"
                        maxlength="1000"
                        placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."
                        class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >{{ old('comment') }}</textarea
                    >
                    @error ('comment')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium"
                    >
                        Gửi đánh giá
                    </button>
                    <a
                        href="{{ route('orders.show', $order) }}"
                        class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg"
                    >
                        Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
