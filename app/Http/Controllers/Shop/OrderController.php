<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Xử lý các yêu cầu liên quan đến đơn hàng. */
class OrderController extends Controller
{
    /**
     * Danh sách đơn hàng của user hoặc guest (qua session).
     */
    public function index(Request $request): View
    {
        if (Auth::check()) {
            // User đã đăng nhập: chỉ lấy đơn của chính mình
            $orders = Order::with([
                'items:id,order_id,product_id',
                'reviews:id,order_id,product_id',
            ])
                ->where('user_id', Auth::id())
                ->withCount('items')
                ->latest('id')
                ->paginate(10);
        } else {
            // Guest: lấy từ session guest_order_ids
            $ids = session('guest_order_ids', []);

            $orders = Order::with([
                'items:id,order_id,product_id',
                'reviews:id,order_id,product_id',
            ])
                ->whereIn('id', $ids)
                ->withCount('items')
                ->latest('id')
                ->paginate(10);
        }

        return view('shop.orders.index', compact('orders'));
    }

    /**
     * Chi tiết 1 đơn hàng.
     * Authorization copy từ CheckoutController::success().
     */
    public function show(Order $order): View
    {
        $this->authorizeOrderAccess($order);

        $order->load([
            'items',
            'reviews',
            'shippingZone',
            'shippingRate',
            'statusHistory.changedBy:id,full_name',
            'returnRequest',
        ]);

        return view('shop.orders.show', [
            'order' => $order,
            'canCancel' => $this->canCustomerCancel($order),
        ]);
    }

    public function cancel(Order $order, InventoryService $inventoryService): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $canceled = DB::transaction(function () use ($order, $inventoryService): bool {
            // Đọc lại trạng thái hiện tại dưới khóa trước khi quyết định hủy đơn.
            $order = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorizeOrderAccess($order);

            if (! $this->canCustomerCancel($order)) {
                return false;
            }

            $previousStatus = $order->status;
            $order->update(['status' => 'cancelled']);
            $inventoryService->restore($order);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $previousStatus,
                'to_status' => 'cancelled',
                'note' => 'Khách hàng đã hủy đơn hàng.',
                'changed_by_user_id' => Auth::id(),
            ]);

            return true;
        });

        if (! $canceled) {
            return back()->with('error', 'Chỉ có thể hủy đơn COD đang chờ xử lý và chưa thanh toán.');
        }

        return back()->with('success', 'Đã hủy đơn hàng '.$order->order_number.'.');
    }

    private function authorizeOrderAccess(Order $order): void
    {
        if (Auth::check()) {
            abort_unless($order->user_id === Auth::id(), 403, 'Bạn không có quyền xem đơn này.');

            return;
        }

        $allowed = in_array($order->id, session('guest_order_ids', []), true);
        abort_unless($allowed, 403, 'Bạn không có quyền xem đơn này.');
    }

    private function canCustomerCancel(Order $order): bool
    {
        // Chỉ cho hủy COD để tránh race giữa thao tác khách và callback thanh toán VNPay.
        return $order->status === 'pending'
            && $order->payment_status === 'unpaid'
            && $order->payment_method === 'cod';
    }
}
