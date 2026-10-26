<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\VNPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/** Xử lý các yêu cầu liên quan đến thanh toán. */
class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
        private VNPayService $vnpayService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $cart = $this->cartService->current();

        if ($cart->items()->count() === 0) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Giỏ hàng đang trống, vui lòng thêm sản phẩm trước khi thanh toán.');
        }

        $cart->load(['items.product', 'items.variant']);

        // Tính subtotal + tổng weight để filter rate
        $subtotal = (int) $cart->items->sum(fn ($i) => $i->unit_price * $i->quantity);
        $totalWeightGram = (int) $cart->items->sum(function ($i) {
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

        return view('shop.checkout.index', compact('cart', 'zones', 'user'));
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

        try {
            $order = $this->orderService->placeOrder($data);
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

            return redirect()->away($paymentUrl);
        }

        if ($order->payment_method === 'cod') {
            $this->cartService->clear();
        }

        // COD: flow cũ — vào trang success luôn
        return redirect()
            ->route('checkout.success', $order)
            ->with('success', 'Đặt hàng thành công! Mã đơn: '.$order->order_number);
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
