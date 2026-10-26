{{-- Mẫu hóa đơn PDF. --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Hóa đơn {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 15mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            color: #222;
            line-height: 1.5;
        }
        h1,
        h2,
        h3 {
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .header td {
            vertical-align: top;
            padding: 0;
        }
        .shop-name {
            font-size: 20pt;
            font-weight: bold;
            color: #4f46e5;
        }
        .shop-info {
            font-size: 9pt;
            color: #555;
            margin-top: 4px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h2 {
            font-size: 18pt;
            color: #4f46e5;
        }
        .invoice-title .meta {
            font-size: 10pt;
            color: #555;
            margin-top: 4px;
        }

        .info-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .info-box td {
            width: 50%;
            vertical-align: top;
            padding: 8px 10px;
            background: #f7f7fb;
            border: 1px solid #e5e7eb;
            font-size: 10pt;
        }
        .info-box .label {
            font-weight: bold;
            color: #4f46e5;
            display: block;
            margin-bottom: 4px;
            font-size: 10pt;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .items thead th {
            background: #4f46e5;
            color: #fff;
            padding: 8px 6px;
            text-align: left;
            font-size: 10pt;
            border: 1px solid #4f46e5;
        }
        .items tbody td {
            padding: 8px 6px;
            border: 1px solid #e5e7eb;
            font-size: 10pt;
            vertical-align: top;
        }
        .items tbody tr:nth-child(even) {
            background: #fafafa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .nowrap {
            white-space: nowrap;
        }

        .totals {
            width: 55%;
            margin-left: 45%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        .totals td {
            padding: 5px 8px;
        }
        .totals .label {
            color: #555;
        }
        .totals .grand-total td {
            border-top: 2px solid #4f46e5;
            font-size: 13pt;
            font-weight: bold;
            color: #4f46e5;
            padding-top: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 9pt;
            border-radius: 4px;
            background: #eef2ff;
            color: #4f46e5;
            margin-left: 4px;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }

        .note-box {
            margin-top: 12px;
            padding: 8px 10px;
            background: #fffbea;
            border-left: 3px solid #f59e0b;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    {{-- ===== HEADER ===== --}}
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="shop-name">MTS</div>
                    <div class="shop-info">
                        Website thương mại điện tử B2C<br />
                        Hotline: 1900-xxxx &nbsp;|&nbsp; Email:
                        support@mts.local
                    </div>
                </td>
                <td class="invoice-title">
                    <h2>HÓA ĐƠN</h2>
                    <div class="meta">
                        Mã đơn: <strong>{{ $order->order_number }}</strong
                        ><br />
                        Ngày đặt: {{ $order->placed_at?->format('d/m/Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ===== NGƯỜI BÁN / NGƯỜI NHẬN ===== --}}
    <table class="info-box">
        <tr>
            <td>
                <span class="label">Người nhận hàng</span>
                <div><strong>{{ $order->recipient_name }}</strong></div>
                <div>ĐT: {{ $order->recipient_phone }}</div>
                @if ($order->recipient_email)
                    <div>Email: {{ $order->recipient_email }}</div>
                @endif
                <div>Địa chỉ: {{ $order->shipping_address }}</div>
            </td>
            <td>
                <span class="label">Thông tin đơn hàng</span>
                <div>
                    Phương thức TT:
                    @if ($order->payment_method === 'cod')
                        COD
                    @elseif ($order->payment_method === 'vnpay')
                        VNPay
                    @else
                        {{ $order->payment_method }}
                    @endif
                </div>
                <div>
                    Trạng thái TT:
                    <strong
                        >{{ $order->payment_status === 'paid' ? 'Đã thanh toán' : ($order->payment_status === 'unpaid' ? 'Chưa thanh toán' : 'Đã hoàn tiền') }}</strong
                    >
                </div>
                <div>Vận chuyển: {{ $order->shipping_rate_name }}</div>
                @if ($order->shippingZone)
                    <div>Khu vực: {{ $order->shippingZone->name }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ===== BẢNG SẢN PHẨM ===== --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%" class="text-center">#</th>
                <th style="width: 45%">Sản phẩm</th>
                <th style="width: 15%" class="text-right">Đơn giá</th>
                <th style="width: 10%" class="text-center">SL</th>
                <th style="width: 25%" class="text-right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if ($item->variant_name)
                            <div style="color: #666; font-size: 9pt">
                                {{ $item->variant_name }}
                            </div>
                        @endif
                        @if ($item->sku)
                            <div style="color: #999; font-size: 8pt">
                                SKU: {{ $item->sku }}
                            </div>
                        @endif
                    </td>
                    <td class="text-right nowrap">
                        {{ number_format($item->unit_price, 0, ',', '.') }} ₫
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right nowrap">
                        {{ number_format($item->line_total, 0, ',', '.') }} ₫
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===== TỔNG TIỀN ===== --}}
    <table class="totals">
        <tr>
            <td class="label">Tạm tính:</td>
            <td class="text-right nowrap">
                {{ number_format($order->subtotal, 0, ',', '.') }} ₫
            </td>
        </tr>
        @if ($order->discount_amount > 0)
            <tr>
                <td class="label">
                    Giảm giá
                    @if ($order->coupon_code) ({{ $order->coupon_code }})@endif
                    :
                </td>
                <td class="text-right nowrap" style="color: #dc2626">
                    -{{ number_format($order->discount_amount, 0, ',', '.') }} ₫
                </td>
            </tr>
        @endif
        <tr>
            <td class="label">Phí vận chuyển:</td>
            <td class="text-right nowrap">
                {{ number_format($order->shipping_fee, 0, ',', '.') }} ₫
            </td>
        </tr>
        <tr class="grand-total">
            <td class="text-right">TỔNG CỘNG:</td>
            <td class="text-right nowrap">
                {{ number_format($order->total, 0, ',', '.') }} ₫
            </td>
        </tr>
    </table>

    @if ($order->note)
        <div class="note-box"><strong>Ghi chú:</strong> {{ $order->note }}</div>
    @endif

    {{-- ===== FOOTER ===== --}}
    <div class="footer">
        Hóa đơn được in lúc {{ now()->format('d/m/Y H:i') }} — Cảm ơn quý khách
        đã mua hàng tại MTS!
    </div>
</body>
</html>
