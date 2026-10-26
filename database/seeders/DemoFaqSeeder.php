<?php

namespace Database\Seeders;

use App\Models\Faq;
// Tạo dữ liệu câu hỏi thường gặp mẫu.

use Illuminate\Database\Seeder;

class DemoFaqSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // Đơn hàng
            ['order',    'Làm sao để kiểm tra tình trạng đơn hàng?',
                'Bạn vào mục "Đơn hàng" ở menu trên cùng (sau khi đăng nhập), hoặc dùng link tra cứu trong email xác nhận đơn hàng.'],
            ['order',    'Tôi có thể hủy đơn hàng sau khi đặt không?',
                'Bạn có thể hủy đơn khi đơn còn ở trạng thái "Chờ xử lý" hoặc "Đã xác nhận". Sau khi đơn chuyển sang "Đang giao", vui lòng liên hệ hotline để được hỗ trợ.'],
            ['order',    'Đơn hàng của tôi bị sai sản phẩm thì phải làm sao?',
                'Vui lòng liên hệ hotline 1900-xxxx hoặc gửi yêu cầu đổi trả trong vòng 7 ngày kể từ khi nhận hàng. Shop sẽ hỗ trợ đổi sản phẩm đúng hoặc hoàn tiền.'],

            // Thanh toán
            ['payment',  'Shop hỗ trợ những hình thức thanh toán nào?',
                'Hiện tại shop hỗ trợ 2 hình thức: (1) Thanh toán khi nhận hàng (COD), (2) Thanh toán online qua VNPay (ATM/Visa/Master/QR).'],
            ['payment',  'Thanh toán VNPay nhưng chưa nhận được xác nhận?',
                'Vui lòng đợi 5-10 phút để hệ thống VNPay đồng bộ. Nếu quá 30 phút chưa nhận được email xác nhận, liên hệ hotline kèm mã giao dịch VNPay.'],
            ['payment',  'Tôi muốn xuất hóa đơn VAT thì làm thế nào?',
                'Vui lòng ghi chú "Cần hóa đơn VAT" + thông tin công ty vào ô ghi chú khi đặt hàng. Nhân viên sẽ liên hệ xác nhận và gửi hóa đơn qua email.'],

            // Vận chuyển
            ['shipping', 'Phí vận chuyển được tính như thế nào?',
                'Phí vận chuyển phụ thuộc vào khu vực (Miền Bắc / Miền Trung / Miền Nam) và được hiển thị rõ ở bước thanh toán. Đơn hàng từ 500.000₫ được miễn phí vận chuyển.'],
            ['shipping', 'Thời gian giao hàng là bao lâu?',
                'Miền Bắc: 1-2 ngày làm việc. Miền Trung: 2-3 ngày. Miền Nam: 3-5 ngày. Thời gian có thể lâu hơn vào dịp lễ, Tết.'],
            ['shipping', 'Tôi có thể đổi địa chỉ giao hàng sau khi đặt không?',
                'Bạn có thể đổi địa chỉ khi đơn còn ở trạng thái "Chờ xử lý". Sau đó vui lòng liên hệ hotline để được hỗ trợ.'],

            // Đổi trả
            ['return',   'Tôi muốn đổi/trả hàng thì cần làm gì?',
                'Trong vòng 7 ngày kể từ khi nhận hàng, bạn vào "Đơn hàng" → chọn đơn → bấm "Yêu cầu trả hàng". Điền lý do và gửi. Shop sẽ xử lý trong 1-2 ngày làm việc.'],
            ['return',   'Khi nào tôi nhận được tiền hoàn?',
                'Sau khi shop nhận hàng trả và kiểm tra, tiền hoàn sẽ được chuyển trong 3-5 ngày làm việc qua phương thức bạn chọn (tiền mặt / chuyển khoản / VNPay).'],

            // Tài khoản
            ['account',  'Tôi quên mật khẩu thì phải làm sao?',
                'Bạn bấm "Quên mật khẩu" ở trang đăng nhập, nhập email đã đăng ký. Hệ thống sẽ gửi link đặt lại mật khẩu qua email trong vài phút.'],
        ];

        $sort = 1;
        foreach ($data as [$category, $question, $answer]) {
            Faq::updateOrCreate(
                ['question' => $question],
                [
                    'answer' => $answer,
                    'category' => $category,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]
            );
            $sort++;
        }

        $this->command->info('Đã seed '.count($data).' FAQs.');
    }
}
