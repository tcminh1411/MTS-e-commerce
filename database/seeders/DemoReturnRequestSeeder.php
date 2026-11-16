<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use RuntimeException;

// Mục đích: tạo tối đa 15 yêu cầu trả hàng demo theo các trạng thái.
// Phụ thuộc: DemoOrderSeeder, UserSeeder, DemoStockSeeder.
class DemoReturnRequestSeeder extends Seeder
{
    private const MAX_RETURN_REQUESTS = 15;

    private const STATUS_WEIGHTS = [
        'pending' => 6,
        'approved' => 4,
        'rejected' => 3,
        'refunded' => 2,
    ];

    public function run(InventoryService $inventoryService): void
    {
        $existingCount = ReturnRequest::count();
        if ($existingCount >= self::MAX_RETURN_REQUESTS) {
            $this->command->info('DemoReturnRequestSeeder: đã đủ dữ liệu, bỏ qua.');

            return;
        }

        $eligibleOrders = Order::query()
            ->where('status', 'completed')
            ->whereNotIn('id', ReturnRequest::pluck('order_id'))
            ->orderBy('id')
            ->limit(self::MAX_RETURN_REQUESTS - $existingCount)
            ->get();

        if ($eligibleOrders->isEmpty()) {
            throw new RuntimeException('DemoReturnRequestSeeder cần order completed chưa có yêu cầu trả hàng.');
        }

        $reasons = [
            'Sản phẩm bị lỗi kỹ thuật, màn hình hiển thị không ổn định sau 2 ngày sử dụng.',
            'Giao sai mẫu so với đơn đặt hàng. Đã liên hệ shop nhưng chưa được phản hồi.',
            'Sản phẩm không đúng như mô tả, cấu hình thấp hơn quảng cáo.',
            'Hàng bị trầy xước nhẹ ở góc máy, không ảnh hưởng chức năng nhưng không hài lòng.',
            'Muốn đổi sang màu khác nhưng shop hết hàng.',
            'Pin tụt nhanh bất thường, sạc không vào sau 1 tuần sử dụng.',
            'Đóng gói sơ sài, hộp bị móp khi nhận hàng.',
        ];

        $adminId = User::where('email', 'admin@example.com')->value('id');
        if (! $adminId) {
            throw new RuntimeException('Chạy UserSeeder trước DemoReturnRequestSeeder.');
        }

        $now = now();

        $statusList = [];
        foreach (self::STATUS_WEIGHTS as $status => $count) {
            $statusList = array_merge($statusList, array_fill(0, $count, $status));
        }

        $created = 0;

        foreach ($eligibleOrders as $i => $order) {
            if (! isset($statusList[$i])) {
                break;
            }
            $status = $statusList[$i];
            $reason = $reasons[array_rand($reasons)];
            $requestedAt = $now->copy()->subDays(random_int(1, 20));

            $data = [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'reason' => $reason,
                'status' => $status,
                'requested_at' => $requestedAt,
            ];

            if ($status === 'approved') {
                $data['admin_note'] = 'Đã xác nhận lỗi từ nhà sản xuất, đồng ý đổi trả.';
                $data['refund_amount'] = (int) round($order->total);
                $data['refund_method'] = 'bank_transfer';
                $data['processed_by_user_id'] = $adminId;
                $data['processed_at'] = $requestedAt->copy()->addDays(2);
            } elseif ($status === 'rejected') {
                $data['admin_note'] = 'Sản phẩm không có lỗi kỹ thuật theo kiểm tra của bộ phận kỹ thuật. Từ chối yêu cầu.';
                $data['processed_by_user_id'] = $adminId;
                $data['processed_at'] = $requestedAt->copy()->addDays(1);
            } elseif ($status === 'refunded') {
                $data['admin_note'] = 'Đã hoàn tiền qua chuyển khoản.';
                $data['refund_amount'] = (int) round($order->total);
                $data['refund_method'] = 'bank_transfer';
                $data['processed_by_user_id'] = $adminId;
                $data['processed_at'] = $requestedAt->copy()->addDays(2);
                $data['refunded_at'] = $requestedAt->copy()->addDays(4);
            }

            $returnRequest = ReturnRequest::create($data);

            if ($status === 'refunded') {
                $inventoryService->restoreReturnedOrder($order, $returnRequest);
                $order->update(['payment_status' => 'refunded']);
            }

            $created++;
        }

        $this->command->info(
            "✅ DemoReturnRequestSeeder: tạo {$created} return requests "
            .'('.self::STATUS_WEIGHTS['pending'].' pending, '.self::STATUS_WEIGHTS['approved'].' approved, '
            .self::STATUS_WEIGHTS['rejected'].' rejected, '.self::STATUS_WEIGHTS['refunded'].' refunded).'
        );
    }
}
