<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\VNPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Xử lý các yêu cầu liên quan đến thanh toán VNPay. */
class VNPayController extends Controller
{
    public function __construct(
        private VNPayService $vnpayService,
        private CartService $cartService,
    ) {}

    /**
     * User quay về sau khi thanh toán xong / hủy trên cổng VNPay.
     * Đây là nơi DUY NHẤT clear cart khi payment_method = vnpay.
     */
    public function return(Request $request)
    {
        $params = $request->all();
        Log::info('VNPay Return:', $params);

        // 1. Verify chữ ký
        if (! $this->vnpayService->verifySignature($params)) {
            return redirect()->route('cart.index')
                ->with('error', 'Chữ ký VNPay không hợp lệ.');
        }

        // 2. Load order
        $order = Order::where('order_number', $params['vnp_TxnRef'] ?? null)->first();

        if (! $order) {
            return redirect()->route('cart.index')
                ->with('error', 'Không tìm thấy đơn hàng.');
        }

        // 3. Thành công → update + clear cart
        if ($this->vnpayService->isSuccessResponse($params)) {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                ]);
            }

            // Giờ mới xóa cart — user đã thanh toán xong
            $this->cartService->clear();

            return redirect()
                ->route('checkout.success', $order)
                ->with('success', 'Thanh toán thành công! Mã đơn: '.$order->order_number);
        }

        // 4. Hủy / thất bại → set cancelled + hoàn kho, KHÔNG clear cart
        //    (user vẫn còn giỏ để đặt lại)
        if ($order->status !== 'cancelled') {
            DB::transaction(function () use ($order) {
                $order->update(['status' => 'cancelled']);
                app(InventoryService::class)->restore($order);
            });
        }

        return redirect()->route('cart.index')
            ->with('error', 'Thanh toán không thành công hoặc đã bị hủy. Đơn hàng '
                .$order->order_number.' đã bị hủy, giỏ hàng của bạn vẫn được giữ nguyên để đặt lại.');
    }

    /**
     * IPN — VNPay gọi server-to-server (không có session user).
     * Chỉ update payment status, KHÔNG clear cart (không có context cart).
     */
    public function ipn(Request $request)
    {
        $params = $request->all();
        Log::info('VNPay IPN:', $params);

        if (! $this->vnpayService->verifySignature($params)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $order = Order::where('order_number', $params['vnp_TxnRef'] ?? null)->first();

        if (! $order) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        // VNPay gửi số tiền đã nhân 100
        $expectedAmount = (int) round($order->total) * 100;
        if ((int) ($params['vnp_Amount'] ?? 0) !== $expectedAmount) {
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        // Idempotent: đã xử lý rồi thì báo lại OK
        if ($order->payment_status === 'paid') {
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        if ($this->vnpayService->isSuccessResponse($params)) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);
        } else {
            // Hủy/thất bại → hoàn kho (idempotent, không sợ hoàn 2 lần)
            DB::transaction(function () use ($order) {
                $order->update(['status' => 'cancelled']);
                app(InventoryService::class)->restore($order);
            });
        }

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }
}
