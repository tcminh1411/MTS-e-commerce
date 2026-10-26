{{-- Mẫu giao diện bảng tổng quan trong trang quản trị. --}}
@extends ('layouts.admin')

@section ('title', 'Dashboard')
@section ('page_title', 'Dashboard')

@section ('content')
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Bảng điều khiển Admin</h1>
        <p class="text-sm text-gray-500">
            Xin chào, {{ Auth::user()->full_name }} — Vai trò: {{ Auth::user()->getRoleNames()->implode(', ') }}
        </p>
    </div>

    {{-- ===== KPI CARDS ===== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">Tổng doanh thu</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1">
                {{ number_format($totalRevenue, 0, ',', '.') }} ₫
            </div>
            <div class="text-xs text-gray-400 mt-1">
                Đơn hoàn thành + đang giao
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">Tổng đơn hàng</div>
            <div class="text-2xl font-bold text-blue-600 mt-1">
                {{ number_format($totalOrders) }}
            </div>
            <div class="text-xs text-gray-400 mt-1">Tất cả trạng thái</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">Người dùng</div>
            <div class="text-2xl font-bold text-purple-600 mt-1">
                {{ number_format($totalUsers) }}
            </div>
            <div class="text-xs text-gray-400 mt-1">Tổng tài khoản</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">Sản phẩm đã bán</div>
            <div class="text-2xl font-bold text-orange-600 mt-1">
                {{ number_format($totalProductsSold) }}
            </div>
            <div class="text-xs text-gray-400 mt-1">
                Tổng số lượng (đơn hoàn thành + đang giao)
            </div>
        </div>
    </div>

    {{-- ===== ROW 1: Revenue line + Status doughnut ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold mb-3">Doanh thu 30 ngày gần nhất</h3>
            <div style="height: 280px">
                <canvas
                    id="revenueChart"
                    data-labels='@json($revenueLabels)'
                    data-values='@json($revenueData)'
                ></canvas>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold mb-3">Đơn theo trạng thái</h3>
            <div style="height: 280px">
                <canvas
                    id="statusChart"
                    data-labels='@json($statusLabels)'
                    data-values='@json($statusData)'
                ></canvas>
            </div>
        </div>
    </div>

    {{-- ===== ROW 2: Top products bar + New users line ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold mb-3">Top 5 sản phẩm bán chạy</h3>
            <div style="height: 260px">
                <canvas
                    id="topProductsChart"
                    data-labels='@json($topProductsLabels)'
                    data-values='@json($topProductsData)'
                ></canvas>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold mb-3">Người dùng mới 30 ngày</h3>
            <div style="height: 260px">
                <canvas
                    id="newUsersChart"
                    data-labels='@json($userLabels)'
                    data-values='@json($userData)'
                ></canvas>
            </div>
        </div>
    </div>

    {{-- ===== LOW STOCK ===== --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold mb-3">⚠️ Sản phẩm sắp hết hàng</h3>
        @if ($lowStocks->isEmpty())
            <p class="text-sm text-gray-500">Không có sản phẩm nào sắp hết hàng.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="bg-gray-50 text-left text-xs uppercase text-gray-500"
                    >
                        <tr>
                            <th class="px-3 py-2">Sản phẩm</th>
                            <th class="px-3 py-2">Phân loại</th>
                            <th class="px-3 py-2">Kho</th>
                            <th class="px-3 py-2 text-right">Tồn</th>
                            <th class="px-3 py-2 text-right">Ngưỡng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($lowStocks as $inv)
                            <tr>
                                <td class="px-3 py-2">
                                    {{ $inv->variant?->product?->name ?? '(đã xoá)' }}
                                </td>
                                <td class="px-3 py-2 text-gray-600">
                                    {{ $inv->variant?->display_name }}
                                </td>
                                <td class="px-3 py-2 text-gray-600">
                                    {{ $inv->warehouse?->name }}
                                </td>
                                <td
                                    class="px-3 py-2 text-right font-semibold text-red-600"
                                >
                                    {{ $inv->quantity }}
                                </td>
                                <td class="px-3 py-2 text-right text-gray-600">
                                    {{ $inv->low_stock_threshold }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== Chart.js CDN + Init (đặt cuối content vì layout admin KHÔNG có @stack('scripts')) ===== --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const readData = (id) => {
                const el = document.getElementById(id);
                if (!el) return null;
                return {
                    el,
                    labels: JSON.parse(el.dataset.labels || '[]'),
                    values: JSON.parse(el.dataset.values || '[]'),
                };
            };

            // 1. Doanh thu (line)
            const rev = readData('revenueChart');
            if (rev) {
                new Chart(rev.el, {
                    type: 'line',
                    data: {
                        labels: rev.labels,
                        datasets: [
                            {
                                label: 'Doanh thu (₫)',
                                data: rev.values,
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.3,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (v) =>
                                        Number(v).toLocaleString('vi-VN'),
                                },
                            },
                        },
                    },
                });
            }

            // 2. Trạng thái (doughnut)
            const st = readData('statusChart');
            if (st) {
                new Chart(st.el, {
                    type: 'doughnut',
                    data: {
                        labels: st.labels,
                        datasets: [
                            {
                                data: st.values,
                                backgroundColor: [
                                    '#fbbf24',
                                    '#3b82f6',
                                    '#8b5cf6',
                                    '#10b981',
                                    '#ef4444',
                                ],
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });
            }

            // 3. Top sản phẩm (bar)
            const tp = readData('topProductsChart');
            if (tp) {
                new Chart(tp.el, {
                    type: 'bar',
                    data: {
                        labels: tp.labels,
                        datasets: [
                            {
                                label: 'Số lượng bán',
                                data: tp.values,
                                backgroundColor: 'rgb(16, 185, 129)',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                        },
                    },
                });
            }

            // 4. User mới (line)
            const nu = readData('newUsersChart');
            if (nu) {
                new Chart(nu.el, {
                    type: 'line',
                    data: {
                        labels: nu.labels,
                        datasets: [
                            {
                                label: 'Người dùng mới',
                                data: nu.values,
                                borderColor: 'rgb(168, 85, 247)',
                                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                                fill: true,
                                tension: 0.3,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                        },
                    },
                });
            }
        })();
    </script>
@endsection
