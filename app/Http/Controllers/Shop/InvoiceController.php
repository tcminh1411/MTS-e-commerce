<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Xử lý các yêu cầu liên quan đến hóa đơn. */
class InvoiceController extends Controller
{
    /**
     * Xuất PDF hóa đơn. Dùng chung cho user (own order) và admin.
     */
    public function download(Order $order): Response
    {
        // Admin/staff: full access
        // Customer: chỉ đơn của mình
        // Guest: chỉ đơn có trong session
        $user = Auth::user();

        if ($user && $user->hasAnyRole(['admin', 'staff'])) {
            // OK — admin/staff xem mọi đơn
        } elseif (Auth::check()) {
            abort_unless($order->user_id === Auth::id(), 403, 'Bạn không có quyền in hóa đơn này.');
        } else {
            $allowed = in_array($order->id, session('guest_order_ids', []), true);
            abort_unless($allowed, 403, 'Bạn không có quyền in hóa đơn này.');
        }

        $order->load(['items', 'shippingZone', 'shippingRate']);

        $pdf = Pdf::loadView('pdf.invoice', compact('order'))
            ->setPaper('a4', 'portrait');

        $filename = 'hoadon-'.$order->order_number.'.pdf';

        // stream(): mở trên browser (dễ demo), user tự Save/Print
        return $pdf->stream($filename);
    }
}
