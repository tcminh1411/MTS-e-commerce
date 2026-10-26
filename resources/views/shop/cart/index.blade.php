{{-- Mẫu giao diện danh sách giỏ hàng phía cửa hàng. --}}
@extends ('layouts.storefront')
@section ('title', 'Giỏ hàng')

@section ('content')
    <h1 class="text-2xl font-bold mb-6">Giỏ hàng</h1>

    @if ($cart->items->isEmpty())
        <div class="bg-white rounded-xl p-10 text-center text-gray-500">
            Giỏ hàng trống.
            <a
                href="{{ route('products.index') }}"
                class="text-indigo-600 hover:underline"
                >Mua sắm ngay →</a
            >
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div
                class="md:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden"
            >
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Sản phẩm</th>
                            <th class="px-4 py-3 text-right">Giá</th>
                            <th class="px-4 py-3 text-center">SL</th>
                            <th class="px-4 py-3 text-right">Tổng</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($cart->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($item->product->thumbnail)
                                            <img
                                                src="{{ $item->product->thumbnail }}"
                                                class="w-14 h-14 object-cover rounded"
                                            />
                                        @endif
                                        <div>
                                            <a
                                                href="{{ route('products.show', $item->product->slug) }}"
                                                class="font-medium hover:text-indigo-600"
                                                >{{ $item->product->name }}</a
                                            >
                                            @if ($item->variant)
                                                <div
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{ $item->variant->name }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ number_format($item->unit_price) }}đ
                                </td>
                                <td class="px-4 py-3">
                                    <form
                                        method="POST"
                                        action="{{ route('cart.update', $item) }}"
                                        class="flex items-center justify-center gap-1"
                                    >
                                        @csrf
                                        @method ('PATCH')
                                        <input
                                            type="number"
                                            name="quantity"
                                            value="{{ $item->quantity }}"
                                            min="0"
                                            max="99"
                                            class="w-14 border rounded text-center px-2 py-1"
                                        />
                                        <button class="text-xs text-indigo-600">
                                            ↻
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold">
                                    {{ number_format($item->line_total) }}đ
                                </td>
                                <td class="px-2 py-3">
                                    <form
                                        method="POST"
                                        action="{{ route('cart.destroy', $item) }}"
                                    >
                                        @csrf
                                        @method ('DELETE')
                                        <button
                                            class="text-red-500 hover:underline text-xs"
                                        >
                                            ✕
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="px-4 py-3 border-t flex justify-between">
                    <a
                        href="{{ route('products.index') }}"
                        class="text-indigo-600 text-sm"
                        >← Tiếp tục mua</a
                    >
                    <form method="POST" action="{{ route('cart.clear') }}">
                        @csrf
                        @method ('DELETE')
                        <button class="text-red-500 text-sm">Xoá tất cả</button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-5 h-fit">
                <h2 class="font-semibold mb-3">Tóm tắt</h2>
                <div class="flex justify-between py-2 text-sm border-b">
                    <span>Tạm tính ({{ $cart->total_quantity }} SP)</span>
                    <span>{{ number_format($cart->subtotal) }}đ</span>
                </div>
                <div class="flex justify-between py-2 text-sm border-b">
                    <span>Phí vận chuyển</span>
                    <span class="text-gray-500">Tính ở checkout</span>
                </div>
                <div class="flex justify-between py-3 font-bold text-lg">
                    <span>Tổng</span>
                    <span class="text-red-600"
                        >{{ number_format($cart->subtotal) }}đ</span
                    >
                </div>
                @if (Route::has('checkout.index'))
                    <a
                        href="{{ route('checkout.index') }}"
                        class="block text-center mt-4 px-4 py-3 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700"
                    >
                        Tiến hành thanh toán
                    </a>
                @else
                    <button
                        disabled
                        class="block w-full text-center mt-4 px-4 py-3 rounded-lg bg-gray-300 text-gray-500 font-medium cursor-not-allowed"
                        title="Chức năng đang phát triển"
                    >
                        Thanh toán (đang phát triển)
                    </button>
                @endif
            </div>
        </div>
    @endif
@endsection
