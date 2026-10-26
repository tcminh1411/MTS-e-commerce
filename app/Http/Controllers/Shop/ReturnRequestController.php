<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Xử lý các yêu cầu liên quan đến yêu cầu trả hàng. */
class ReturnRequestController extends Controller
{
    /**
     * Form tạo yêu cầu trả hàng.
     */
    public function create(Order $order): View|RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'completed') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Chỉ có thể yêu cầu trả hàng khi đơn đã hoàn thành.');
        }

        if ($order->returnRequest) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Đơn hàng này đã có yêu cầu trả hàng.');
        }

        return view('shop.orders.return-create', compact('order'));
    }

    /**
     * Lưu yêu cầu trả hàng.
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'completed') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Chỉ có thể yêu cầu trả hàng khi đơn đã hoàn thành.');
        }

        if ($order->returnRequest) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Đơn hàng này đã có yêu cầu trả hàng.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do trả hàng.',
            'reason.min' => 'Lý do cần ít nhất 10 ký tự.',
            'reason.max' => 'Lý do tối đa 2000 ký tự.',
        ]);

        ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'reason' => $data['reason'],
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Đã gửi yêu cầu trả hàng. Chúng tôi sẽ xử lý trong thời gian sớm nhất.');
    }

    /**
     * Authorization: giống OrderController::show().
     */
    private function authorizeOrder(Order $order): void
    {
        if (Auth::check()) {
            abort_unless($order->user_id === Auth::id(), 403, 'Bạn không có quyền thao tác đơn này.');
        } else {
            $allowed = in_array($order->id, session('guest_order_ids', []), true);
            abort_unless($allowed, 403, 'Bạn không có quyền thao tác đơn này.');
        }
    }
}
