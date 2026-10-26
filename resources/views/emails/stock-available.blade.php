{{-- Email thông báo khi sản phẩm có hàng trở lại. --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Sản phẩm đã có hàng</title>
</head>
<body
    style="
        font-family: Arial, Helvetica, sans-serif;
        line-height: 1.5;
        color: #333;
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
    "
>
    <h2 style="color: #4f46e5">Sản phẩm bạn quan tâm đã có hàng trở lại 🎉</h2>

    <p>Xin chào,</p>

    <p>
        Sản phẩm
        <strong>{{ $variant->product?->name ?? 'N/A' }}</strong>
        @if (! $variant->is_default)
            (phân loại:
            <strong
                >{{ $variant->getAttribute('name') ?? $variant->sku }}</strong
            >)
        @endif
        hiện đã <strong style="color: #16a34a">còn hàng</strong>.
    </p>

    <p>Số lượng hiện có: <strong>{{ $variant->stock }}</strong>.</p>

    <p style="margin: 24px 0">
        <a
            href="{{ url('/products/'.$variant->product?->slug) }}"
            style="
                display: inline-block;
                padding: 10px 20px;
                background: #4f46e5;
                color: #fff;
                text-decoration: none;
                border-radius: 6px;
            "
        >
            Xem sản phẩm
        </a>
    </p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0" />

    <p
        style="font-size: 12px; color: #6b7280"
    >Bạn nhận được email này vì đã đăng ký nhận thông báo khi sản phẩm có hàng.</p>
</body>
</html>
