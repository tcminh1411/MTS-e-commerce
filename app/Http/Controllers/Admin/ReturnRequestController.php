<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Xử lý các yêu cầu liên quan đến yêu cầu trả hàng. */
class ReturnRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = ReturnRequest::with([
            'order:id,order_number,total,user_id',
            'user:id,full_name,email',
        ])
            ->latest('id');

        if ($request->filled('status') && array_key_exists($request->status, ReturnRequest::STATUSES)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->whereHas('order', fn ($qq) => $qq->where('order_number', 'like', "%{$q}%"));
        }

        $returns = $query->paginate(20)->withQueryString();

        return view('admin.returns.index', [
            'returns' => $returns,
            'statuses' => ReturnRequest::STATUSES,
        ]);
    }

    public function show(ReturnRequest $returnRequest): View
    {
        $returnRequest->load([
            'order.items',
            'order.user:id,full_name,email',
            'user:id,full_name,email',
            'processedBy:id,full_name',
        ]);

        return view('admin.returns.show', [
            'return' => $returnRequest,
            'statuses' => ReturnRequest::STATUSES,
            'refundMethods' => ReturnRequest::REFUND_METHODS,
        ]);
    }

    public function approve(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        if ($returnRequest->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể duyệt yêu cầu đang chờ xử lý.');
        }

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'refund_amount' => ['required', 'integer', 'min:0', 'max:'.$returnRequest->order->total],
            'refund_method' => ['required', 'string', 'in:'.implode(',', array_keys(ReturnRequest::REFUND_METHODS))],
        ], [
            'refund_amount.required' => 'Vui lòng nhập số tiền hoàn.',
            'refund_amount.max' => 'Số tiền hoàn không được vượt quá tổng đơn hàng.',
            'refund_method.required' => 'Vui lòng chọn hình thức hoàn tiền.',
            'refund_method.in' => 'Hình thức hoàn tiền không hợp lệ.',
        ]);

        DB::transaction(function () use ($returnRequest, $data) {
            $returnRequest->update([
                'status' => 'approved',
                'admin_note' => $data['admin_note'] ?? null,
                'refund_amount' => $data['refund_amount'],
                'refund_method' => $data['refund_method'],
                'processed_by_user_id' => Auth::id(),
                'processed_at' => now(),
            ]);
        });

        return back()->with('success', 'Đã chấp nhận yêu cầu trả hàng.');
    }

    public function reject(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        if ($returnRequest->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể từ chối yêu cầu đang chờ xử lý.');
        }

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'admin_note.required' => 'Vui lòng nhập lý do từ chối.',
            'admin_note.min' => 'Lý do từ chối cần ít nhất 5 ký tự.',
        ]);

        $returnRequest->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'],
            'processed_by_user_id' => Auth::id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Đã từ chối yêu cầu trả hàng.');
    }

    public function markRefunded(ReturnRequest $returnRequest): RedirectResponse
    {
        if ($returnRequest->status !== 'approved') {
            return back()->with('error', 'Chỉ có thể đánh dấu hoàn tiền cho yêu cầu đã chấp nhận.');
        }

        DB::transaction(function () use ($returnRequest) {
            $returnRequest->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);

            $returnRequest->order->update([
                'payment_status' => 'refunded',
            ]);
        });

        return back()->with('success', 'Đã đánh dấu hoàn tiền thành công.');
    }
}
