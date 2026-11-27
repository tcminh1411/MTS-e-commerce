<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ProductVariantService;
use App\Services\VNPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

/** Xử lý các yêu cầu liên quan đến thanh toán. */
class CheckoutController extends Controller
{
    private const BUY_NOW_SESSION_KEY = 'checkout.buy_now';

    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
        private VNPayService $vnpayService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $isBuyNow = session()->has(self::BUY_NOW_SESSION_KEY);

        if ($isBuyNow) {
            $item = $this->resolveBuyNowItem(session(self::BUY_NOW_SESSION_KEY));

            if (! $item) {
                session()->forget(self::BUY_NOW_SESSION_KEY);

                return redirect()
                    ->route('products.index')
                    ->with('error', 'Sản phẩm hoặc phân loại không còn khả dụng.');
            }

            $checkoutItems = collect([$item]);
        } else {
            $cart = $this->cartService->current();

            if ($cart->items()->count() === 0) {
                return redirect()
                    ->route('cart.index')
                    ->with('error', 'Giỏ hàng đang trống, vui lòng thêm sản phẩm trước khi thanh toán.');
            }

            $this->cartService->refreshPrices($cart);
            $cart->loadMissing(['items.product', 'items.variant']);

            if ($this->cartService->hasInsufficientStock($cart)) {
                return redirect()
                    ->route('cart.index')
                    ->with('error', 'Giỏ hàng có sản phẩm hết hàng hoặc vượt tồn kho. Vui lòng cập nhật giỏ hàng.');
            }

            $checkoutItems = $cart->items;
        }

        // Tính subtotal + tổng weight để filter rate
        $subtotal = (int) $checkoutItems->sum(fn ($i) => $i->unit_price * $i->quantity);
        $totalWeightGram = (int) $checkoutItems->sum(function ($i) {
            $w = $i->variant?->weight_gram ?? $i->product?->weight_gram ?? 0;

            return $i->quantity * $w;
        });

        // Chỉ zone active, chỉ rate active + khả dụng cho subtotal & weight hiện tại
        $zones = ShippingZone::active()
            ->with(['rates' => fn ($q) => $q
                ->where('is_active', true)
                ->forSubtotal($subtotal)
                ->forWeight($totalWeightGram)
                ->orderBy('sort_order'),
            ])
            ->get();

        $user = Auth::user();

        return view('shop.checkout.index', compact('checkoutItems', 'isBuyNow', 'subtotal', 'zones', 'user'));
    }

    public function buyNow(Request $request, ProductVariantService $productVariantService): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('status', 'active')],
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'product_id.exists' => 'Sản phẩm không còn khả dụng.',
            'quantity.min' => 'Số lượng tối thiểu là 1.',
            'quantity.max' => 'Số lượng tối đa là 99.',
        ]);

        $product = Product::query()->active()->find($data['product_id']);

        if (! $product) {
            throw ValidationException::withMessages([
                'product_id' => 'Sản phẩm không còn khả dụng.',
            ]);
        }

        $variantId = $data['variant_id'] ?? null;
        $variant = null;

        if ($product->product_type === 'simple') {
            $variant = $productVariantService->ensureDefaultForSimpleProduct($product);

            if ($variant->status !== 'active'
                || ($variantId !== null && (int) $variantId !== $variant->id)
            ) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Phân loại mặc định không còn khả dụng.',
                ]);
            }
        } elseif ($product->variants()->exists()) {
            if ($variantId === null) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Vui lòng chọn phân loại sản phẩm.',
                ]);
            }

            $variant = $product->variants()
                ->where('status', 'active')
                ->whereKey($variantId)
                ->first();

            if (! $variant) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Phân loại không hợp lệ cho sản phẩm này.',
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'variant_id' => 'Sản phẩm chưa có biến thể để kiểm tra tồn kho.',
            ]);
        }

        if ($variant->stock < $data['quantity']) {
            throw ValidationException::withMessages([
                'variant_id' => $variant->stock === 0
                    ? 'Phân loại này đã hết hàng.'
                    : 'Tồn kho không đủ cho số lượng đã chọn.',
            ]);
        }

        $variantId = $variant->id;

        session()->put(self::BUY_NOW_SESSION_KEY, [
            'product_id' => (int) $product->id,
            'variant_id' => $variantId === null ? null : (int) $variantId,
            'quantity' => (int) $data['quantity'],
        ]);

        return redirect()->route('checkout.index');
    }

    public function place(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_rate_id' => ['required', 'integer', Rule::exists('shipping_rates', 'id')->where('is_active', true)],
            'payment_method' => ['required', Rule::in(['cod', 'vnpay'])],
            'note' => ['nullable', 'string', 'max:500'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ], [
            'recipient_name.required' => 'Vui lòng nhập tên người nhận.',
            'recipient_phone.required' => 'Vui lòng nhập số điện thoại.',
            'recipient_phone.regex' => 'Số điện thoại không hợp lệ.',
            'shipping_address.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'shipping_rate_id.required' => 'Vui lòng chọn phương thức vận chuyển.',
            'shipping_rate_id.exists' => 'Phương thức vận chuyển không hợp lệ.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hỗ trợ.',
            'coupon_code.max' => 'Mã giảm giá tối đa 50 ký tự.',
        ]);

        // Nếu user đã login nhưng bỏ trống email, fallback về email user
        if (Auth::check() && empty($data['recipient_email'])) {
            $data['recipient_email'] = Auth::user()->email;
        }

        $isBuyNow = session()->has(self::BUY_NOW_SESSION_KEY);
        $buyNowItem = $isBuyNow ? session(self::BUY_NOW_SESSION_KEY) : null;

        if ($isBuyNow && ! is_array($buyNowItem)) {
            session()->forget(self::BUY_NOW_SESSION_KEY);

            return back()
                ->withInput()
                ->with('error', 'Thông tin mua ngay không hợp lệ. Vui lòng chọn lại sản phẩm.');
        }

        try {
            $order = $this->orderService->placeOrder($data, $buyNowItem);
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        // Guest: lưu order id vào session để xem lại
        if (! Auth::check()) {
            $ids = session('guest_order_ids', []);
            $ids[] = $order->id;
            session(['guest_order_ids' => array_unique($ids)]);
        }

        // VNPay: redirect sang cổng thanh toán thay vì vào success
        if ($order->payment_method === 'vnpay') {
            $paymentUrl = $this->vnpayService->buildPaymentUrl($order, $request->ip());

            if ($isBuyNow) {
                session()->forget(self::BUY_NOW_SESSION_KEY);
            }

            return redirect()->away($paymentUrl);
        }

        if ($order->payment_method === 'cod') {
            if ($isBuyNow) {
                session()->forget(self::BUY_NOW_SESSION_KEY);
            } else {
                $this->cartService->clear();
            }
        }

        // COD: flow cũ — vào trang success luôn
        return redirect()
            ->route('checkout.success', $order)
            ->with('success', 'Đặt hàng thành công! Mã đơn: '.$order->order_number);
    }

    private function resolveBuyNowItem(mixed $buyNowData): ?CartItem
    {
        if (! is_array($buyNowData)) {
            return null;
        }

        $productId = filter_var($buyNowData['product_id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($buyNowData['quantity'] ?? null, FILTER_VALIDATE_INT);

        if ($productId === false || $quantity === false || $quantity < 1 || $quantity > 99) {
            return null;
        }

        $product = Product::query()
            ->active()
            ->with('variants.attributeValues')
            ->find($productId);

        if (! $product) {
            return null;
        }

        $hasVariants = $product->variants()->exists();
        $variantId = $buyNowData['variant_id'] ?? null;
        $variant = null;

        if ($hasVariants) {
            $variantId = filter_var($variantId, FILTER_VALIDATE_INT);

            if ($variantId === false) {
                return null;
            }

            $variant = $product->variants
                ->first(fn ($productVariant) => $productVariant->id === $variantId && $productVariant->status === 'active');

            if (! $variant || $variant->stock < $quantity) {
                return null;
            }
        } elseif ($variantId !== null) {
            return null;
        }

        $item = new CartItem([
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price' => $variant?->final_price ?? $product->final_price,
        ]);
        $item->setRelation('product', $product);
        $item->setRelation('variant', $variant);

        return $item;
    }

    public function success(Order $order): View
    {
        // Authorization: user login chỉ xem đơn của mình
        if (Auth::check()) {
            abort_unless($order->user_id === Auth::id(), 403, 'Bạn không có quyền xem đơn này.');
        } else {
            // Guest: chỉ xem được nếu order id có trong session
            $allowed = in_array($order->id, session('guest_order_ids', []), true);
            abort_unless($allowed, 403, 'Bạn không có quyền xem đơn này.');
        }

        $order->load(['items', 'shippingZone', 'statusHistory']);

        return view('shop.checkout.success', compact('order'));
    }
}
