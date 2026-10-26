<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoReturnRequestSeeder extends Seeder
{
    public function run(): void
    {
        $eligibleOrders = Order::query()
            ->where('status', 'completed')
            ->whereNotIn('id', ReturnRequest::pluck('order_id'))
            ->inRandomOrder()
            ->limit(15)
            ->get();

        if ($eligibleOrders->isEmpty()) {
            $this->command->warn('⚠️  Không có order completed nào để tạo return request.');

            return;
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
        $now = now();

        $distribution = [
            'pending' => 6,
            'approved' => 4,
            'rejected' => 3,
            'refunded' => 2,
        ];

        $statusList = [];
        foreach ($distribution as $status => $count) {
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

            ReturnRequest::create($data);

            if ($status === 'refunded') {
                $order->update(['payment_status' => 'refunded']);
            }

            $created++;
        }

        $this->command->info(
            "✅ DemoReturnRequestSeeder: tạo {$created} return requests "
            ."({$distribution['pending']} pending, {$distribution['approved']} approved, "
            ."{$distribution['rejected']} rejected, {$distribution['refunded']} refunded)."
        );
    }
}
