{{-- Trang thanh toán đơn hàng. --}}
@extends ('layouts.storefront')

@section ('title', 'Thanh toán')

@section ('content')
    <div class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Thanh toán</h1>

        <form
            method="POST"
            action="{{ route('checkout.place') }}"
            data-subtotal="{{ (int) $cart->items->sum(fn($i) => $i->unit_price * $i->quantity) }}"
            class="grid grid-cols-1 lg:grid-cols-3 gap-6"
        >
            @csrf

            {{-- Cột trái: thông tin giao hàng + vận chuyển + thanh toán --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Thông tin người nhận --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        Thông tin người nhận
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1"
                                >Tên người nhận
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                type="text"
                                name="recipient_name"
                                value="{{ old('recipient_name', Auth::user()?->full_name) }}"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400"
                                required
                            />
                            @error ('recipient_name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1"
                                >Số điện thoại
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                type="text"
                                name="recipient_phone"
                                value="{{ old('recipient_phone', Auth::user()?->phone) }}"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400"
                                required
                            />
                            @error ('recipient_phone')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1"
                                >Email (không bắt buộc)</label
                            >
                            <input
                                type="email"
                                name="recipient_email"
                                value="{{ old('recipient_email', Auth::user()?->email) }}"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400"
                            />
                            @error ('recipient_email')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1"
                                >Địa chỉ giao hàng
                                <span class="text-red-500">*</span></label
                            >
                            <textarea
                                name="shipping_address"
                                rows="3"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400"
                                required
                                >{{ old('shipping_address') }}</textarea
                            >
                            @error ('shipping_address')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1"
                                >Ghi chú</label
                            >
                            <textarea
                                name="note"
                                rows="2"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400"
                                >{{ old('note') }}</textarea
                            >
                        </div>
                    </div>
                </div>

                {{-- Vận chuyển --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        Phương thức vận chuyển
                    </h2>

                    @if ($zones->isEmpty())
                        <p class="text-red-600">Chưa có zone vận chuyển nào active. Vui lòng liên hệ admin.</p>
                    @else
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1"
                                >Khu vực</label
                            >
                            <select
                                id="zone-select"
                                class="w-full border rounded px-3 py-2"
                            >
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone->id }}">
                                        {{ $zone->name }} — {{ implode(', ', $zone->provinces ?? []) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-2" id="rate-list">
                            @foreach ($zones as $zone)
                                @foreach ($zone->rates as $rate)
                                    <label
                                        class="block border rounded p-3 cursor-pointer rate-item hover:bg-gray-50"
                                        data-zone="{{ $zone->id }}"
                                        data-fee="{{ $rate->fee }}"
                                    >
                                        <input
                                            type="radio"
                                            name="shipping_rate_id"
                                            value="{{ $rate->id }}"
                                            class="mr-2"
                                            {{ old('shipping_rate_id') == $rate->id ? 'checked' : '' }}
                                        />
                                        <span
                                            class="font-medium"
                                            >{{ $rate->name }}</span
                                        >
                                        <span class="text-gray-600 ml-2">
                                            — {{ number_format($rate->fee, 0, ',', '.') }} ₫
                                            @if ($rate->estimated_days_min)
                                                (dự kiến {{ $rate->estimated_days_min }}–{{ $rate->estimated_days_max }} ngày)
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                        @error ('shipping_rate_id')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                {{-- Thanh toán --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        Phương thức thanh toán
                    </h2>

                    <label
                        class="block border rounded p-3 cursor-pointer hover:bg-gray-50 mb-2"
                    >
                        <input
                            type="radio"
                            name="payment_method"
                            value="cod"
                            class="mr-2"
                            {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}
                        />
                        <span class="font-medium"
                            >Thanh toán khi nhận hàng (COD)</span
                        >
                    </label>

                    <label
                        class="block border rounded p-3 cursor-pointer hover:bg-gray-50"
                    >
                        <input
                            type="radio"
                            name="payment_method"
                            value="vnpay"
                            class="mr-2"
                            {{ old('payment_method', 'cod') === 'vnpay' ? 'checked' : '' }}
                        />
                        <span class="font-medium">VNPay</span>
                        <span class="text-sm text-gray-500 ml-2"
                            >(Chuyển sang cổng thanh toán VNPay)</span
                        >
                    </label>
                    @error ('payment_method')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Cột phải: tóm tắt đơn hàng --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                    <h2 class="text-lg font-semibold mb-4">Đơn hàng</h2>

                    <div class="space-y-3 mb-4 max-h-64 overflow-y-auto">
                        @foreach ($cart->items as $item)
                            <div class="flex justify-between text-sm">
                                <div class="flex-1 pr-2">
                                    <div class="font-medium">
                                        {{ $item->product?->name ?? '(đã xoá)' }}
                                    </div>
                                    @if ($item->variant)
                                        <div class="text-gray-500">
                                            {{ $item->variant->getAttribute('name') }}
                                        </div>
                                    @endif
                                    <div class="text-gray-500">
                                        SL: {{ $item->quantity }}
                                    </div>
                                </div>
                                <div class="font-medium whitespace-nowrap">
                                    {{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }} ₫
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-3" />

                    {{-- Ô nhập mã giảm giá --}}
                    <div class="mb-4">
                        <label
                            for="coupon_code"
                            class="block text-sm font-medium mb-1"
                            >Mã giảm giá (nếu có)</label
                        >
                        <input
                            type="text"
                            name="coupon_code"
                            id="coupon_code"
                            value="{{ old('coupon_code') }}"
                            placeholder="VD: SALE10"
                            maxlength="50"
                            autocomplete="off"
                            class="w-full border rounded px-3 py-2 uppercase focus:outline-none focus:ring focus:border-blue-400"
                        />
                        @error ('coupon_code')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Mã sẽ được áp dụng khi bạn bấm "Đặt hàng".</p>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Tạm tính</span>
                            <span id="subtotal-display">
                                {{ number_format($cart->items->sum(fn($i) => $i->unit_price * $i->quantity), 0, ',', '.') }} ₫
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span>Phí vận chuyển</span>
                            <span id="shipping-display">0 ₫</span>
                        </div>
                        <hr />
                        <div class="flex justify-between text-lg font-bold">
                            <span>Tổng cộng</span>
                            <span id="total-display">
                                {{ number_format($cart->items->sum(fn($i) => $i->unit_price * $i->quantity), 0, ',', '.') }} ₫
                            </span>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded"
                    >
                        Đặt hàng
                    </button>

                    <a
                        href="{{ route('cart.index') }}"
                        class="block text-center text-sm text-gray-600 mt-3 hover:underline"
                    >
                        ← Quay lại giỏ hàng
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const form = document.querySelector('form[data-subtotal]');
            const subtotal = form ? parseInt(form.dataset.subtotal, 10) : 0;
            const zoneSelect = document.getElementById('zone-select');
            const rateItems = document.querySelectorAll('.rate-item');
            const shipDisplay = document.getElementById('shipping-display');
            const totalDisplay = document.getElementById('total-display');

            function formatVnd(n) {
                return n.toLocaleString('vi-VN') + ' ₫';
            }

            function currentZoneId() {
                return zoneSelect ? String(zoneSelect.value) : null;
            }

            function updateRatesVisibility() {
                const zid = currentZoneId();
                rateItems.forEach((item) => {
                    const match = item.dataset.zone === zid;
                    item.style.display = match ? '' : 'none';
                    if (!match) {
                        const radio = item.querySelector('input[type=radio]');
                        if (radio && radio.checked) radio.checked = false;
                    }
                });
            }

            function updateTotals() {
                const checked = document.querySelector(
                    'input[name=shipping_rate_id]:checked',
                );
                const fee = checked
                    ? parseInt(checked.closest('.rate-item').dataset.fee, 10)
                    : 0;
                shipDisplay.textContent = formatVnd(fee);
                totalDisplay.textContent = formatVnd(subtotal + fee);
            }

            if (zoneSelect) {
                zoneSelect.addEventListener('change', () => {
                    updateRatesVisibility();
                    updateTotals();
                });
            }

            document
                .querySelectorAll('input[name=shipping_rate_id]')
                .forEach((r) => {
                    r.addEventListener('change', updateTotals);
                });

            // Init
            updateRatesVisibility();
            updateTotals();
        })();
    </script>
@endsection
