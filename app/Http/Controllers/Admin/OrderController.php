<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Xử lý các yêu cầu liên quan đến đơn hàng. */
class OrderController extends Controller
{
    /** Các trạng thái hợp lệ của đơn hàng */
    public const STATUSES = [
        'pending' => 'Chờ xử lý',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];

    /** Các trạng thái thanh toán hợp lệ */
    public const PAYMENT_STATUSES = [
        'unpaid' => 'Chưa thanh toán',
        'paid' => 'Đã thanh toán',
        'refunded' => 'Đã hoàn tiền',
    ];

    /* ==================== INDEX ==================== */

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'payment_status' => ['nullable', Rule::in(array_keys(self::PAYMENT_STATUSES))],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $orders = Order::query()
            ->with(['user:id,full_name,email'])
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['payment_status']), fn ($q) => $q->where('payment_status', $filters['payment_status']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $kw = trim($filters['search']);
                $q->where(function ($sub) use ($kw) {
                    $sub->where('order_number', 'like', "%{$kw}%")
                        ->orWhere('recipient_phone', 'like', "%{$kw}%")
                        ->orWhere('recipient_name', 'like', "%{$kw}%")
                        ->orWhere('recipient_email', 'like', "%{$kw}%");
                });
            })
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => self::STATUSES,
            'paymentStatuses' => self::PAYMENT_STATUSES,
        ]);
    }

    /* ==================== SHOW ==================== */

    public function show(Order $order)
    {
        $order->load([
            'user:id,full_name,email',
            'items',
            'shippingZone',
            'shippingRate',
            'statusHistory' => fn ($q) => $q->with('changedBy:id,full_name')->orderBy('id'),
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => self::STATUSES,
            'paymentStatuses' => self::PAYMENT_STATUSES,
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    /* ==================== UPDATE STATUS ==================== */

    public function updateStatus(Request $request, Order $order, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'to_status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')->where('is_active', true),
            ],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'to_status.required' => 'Vui lòng chọn trạng thái mới.',
            'to_status.in' => 'Trạng thái không hợp lệ.',
            'warehouse_id.exists' => 'Kho được chọn không tồn tại hoặc đã ngừng hoạt động.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ]);

        $updated = DB::transaction(function () use ($order, $data, $inventoryService): bool {
            $order = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $from = $order->status;

            if ($data['to_status'] === $from) {
                return false;
            }

            if ($data['to_status'] === 'shipping' && $from !== 'shipping') {
                if (empty($data['warehouse_id'])) {
                    throw ValidationException::withMessages([
                        'warehouse_id' => 'Vui lòng chọn kho để trừ tồn khi chuyển đơn sang đang giao.',
                    ]);
                }

                $inventoryService->deduct($order, (int) $data['warehouse_id']);
            }

            $order->update(['status' => $data['to_status']]);

            // Nếu chuyển sang 'cancelled' → hoàn kho (idempotent)
            if ($data['to_status'] === 'cancelled') {
                $inventoryService->restore($order);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $from,
                'to_status' => $data['to_status'],
                'note' => $data['note'] ?? null,
                'changed_by_user_id' => Auth::id(),
            ]);

            return true;
        });

        if (! $updated) {
            return back()->with('error', 'Trạng thái mới trùng với trạng thái hiện tại.');
        }

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }

    /* ==================== UPDATE PAYMENT STATUS ==================== */

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(array_keys(self::PAYMENT_STATUSES))],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'payment_status.required' => 'Vui lòng chọn trạng thái thanh toán.',
            'payment_status.in' => 'Trạng thái thanh toán không hợp lệ.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ]);

        if ($data['payment_status'] === $order->payment_status) {
            return back()->with('error', 'Trạng thái thanh toán mới trùng với trạng thái hiện tại.');
        }

        DB::transaction(function () use ($order, $data) {
            $from = $order->payment_status;
            $order->update(['payment_status' => $data['payment_status']]);

            $note = '[Payment] '.$from.' → '.$data['payment_status'];
            if (! empty($data['note'])) {
                $note .= ' — '.$data['note'];
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $order->status,
                'to_status' => $order->status,
                'note' => $note,
                'changed_by_user_id' => Auth::id(),
            ]);
        });

        return back()->with('success', 'Đã cập nhật trạng thái thanh toán.');
    }
}
