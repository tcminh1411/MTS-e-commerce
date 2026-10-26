<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;

/** Xử lý các yêu cầu liên quan đến bảng tổng quan. */
class DashboardController extends Controller
{
    /**
     * Dashboard admin: KPI + 4 charts + low stock.
     */
    public function index()
    {
        // Trạng thái được tính vào "doanh thu thực" (đã xác nhận hoặc đang giao)
        $paidStatuses = ['completed', 'shipping'];

        // ===== 1. KPI CARDS =====
        $totalRevenue = (int) Order::whereIn('status', $paidStatuses)->sum('total');
        $totalOrders = Order::count();
        $totalUsers = User::count();
        $totalProductsSold = (int) OrderItem::whereHas('order', function ($q) use ($paidStatuses) {
            $q->whereIn('status', $paidStatuses);
        })->sum('quantity');

        // ===== 2. DOANH THU 30 NGÀY GẦN NHẤT =====
        $startDate = Carbon::today()->subDays(29);

        $revenueRaw = Order::whereIn('status', $paidStatuses)
            ->where('placed_at', '>=', $startDate)
            ->selectRaw('DATE(placed_at) as d, SUM(total) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $revenueLabels = [];
        $revenueData = [];
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $key = $date->toDateString();
            $revenueLabels[] = $date->format('d/m');
            $revenueData[] = (int) ($revenueRaw[$key] ?? 0);
        }

        // ===== 3. ĐƠN THEO STATUS =====
        $statusRaw = Order::selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $statusMap = [
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];
        $statusLabels = [];
        $statusData = [];
        foreach ($statusMap as $key => $label) {
            $statusLabels[] = $label;
            $statusData[] = (int) ($statusRaw[$key] ?? 0);
        }

        // ===== 4. TOP 5 SẢN PHẨM BÁN CHẠY =====
        $topProducts = OrderItem::selectRaw('product_name, SUM(quantity) as total_qty')
            ->whereHas('order', function ($q) use ($paidStatuses) {
                $q->whereIn('status', $paidStatuses);
            })
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $topProductsLabels = $topProducts->pluck('product_name')->all();
        $topProductsData = $topProducts->pluck('total_qty')->map(fn ($v) => (int) $v)->all();

        // ===== 5. USER MỚI 30 NGÀY =====
        $userRaw = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $userLabels = [];
        $userData = [];
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $key = $date->toDateString();
            $userLabels[] = $date->format('d/m');
            $userData[] = (int) ($userRaw[$key] ?? 0);
        }

        // ===== 6. LOW STOCK =====
        $lowStocks = Inventory::whereColumn('quantity', '<=', 'low_stock_threshold')
            ->with(['variant.product', 'warehouse'])
            ->orderBy('quantity')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'totalOrders',
            'totalUsers',
            'totalProductsSold',
            'revenueLabels',
            'revenueData',
            'statusLabels',
            'statusData',
            'topProductsLabels',
            'topProductsData',
            'userLabels',
            'userData',
            'lowStocks'
        ));
    }
}
