<?php

namespace Database\Seeders;

use App\Models\Page;
// Tạo nội dung trang tĩnh minh họa.

use Illuminate\Database\Seeder;

class DemoPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'gioi-thieu',
                'title' => 'Giới thiệu',
                'meta_title' => 'Giới thiệu về ShopDemo',
                'meta_description' => 'ShopDemo — website thương mại điện tử B2C, đồ án tốt nghiệp.',
                'show_in_footer' => true,
                'footer_sort' => 1,
                'body' => <<<'HTML'
<h2>Về ShopDemo</h2>
<p>ShopDemo là website thương mại điện tử B2C được xây dựng trên nền tảng <strong>Laravel 13</strong>, phục vụ nhu cầu mua sắm trực tuyến các sản phẩm công nghệ: điện thoại, laptop, phụ kiện.</p>

<h3>Sứ mệnh</h3>
<p>Mang đến trải nghiệm mua sắm <em>đơn giản — nhanh chóng — an toàn</em> cho khách hàng Việt Nam.</p>

<h3>Giá trị cốt lõi</h3>
<ul>
    <li>Chính hãng 100%, bảo hành đầy đủ.</li>
    <li>Giao hàng toàn quốc, hỗ trợ COD và VNPay.</li>
    <li>Đổi trả trong 7 ngày nếu sản phẩm lỗi.</li>
    <li>Hỗ trợ khách hàng 24/7 qua hotline và email.</li>
</ul>

<h3>Liên hệ</h3>
<p>Email: <a href="mailto:hello@example.com">hello@example.com</a><br>
Hotline: 1900-xxxx<br>
Địa chỉ: Hà Nội, Việt Nam</p>
HTML,
                'is_active' => true,
            ],
            [
                'slug' => 'dieu-khoan',
                'title' => 'Điều khoản sử dụng',
                'meta_title' => 'Điều khoản sử dụng — ShopDemo',
                'meta_description' => 'Điều khoản và điều kiện sử dụng website ShopDemo.',
                'show_in_footer' => true,
                'footer_sort' => 2,
                'body' => <<<'HTML'
<h2>Điều khoản sử dụng</h2>
<p>Khi truy cập và sử dụng website ShopDemo, bạn đồng ý tuân thủ các điều khoản dưới đây.</p>

<h3>1. Tài khoản người dùng</h3>
<ul>
    <li>Bạn có trách nhiệm bảo mật thông tin đăng nhập.</li>
    <li>Không sử dụng tài khoản của người khác mà không được phép.</li>
    <li>Thông tin đăng ký phải chính xác và đầy đủ.</li>
</ul>

<h3>2. Đặt hàng và thanh toán</h3>
<ul>
    <li>Đơn hàng chỉ được xác nhận khi thanh toán thành công (VNPay) hoặc xác nhận COD.</li>
    <li>ShopDemo có quyền hủy đơn nếu phát hiện gian lận.</li>
    <li>Giá sản phẩm đã bao gồm VAT, chưa bao gồm phí vận chuyển.</li>
</ul>

<h3>3. Đổi trả và hoàn tiền</h3>
<ul>
    <li>Áp dụng cho đơn hàng đã hoàn thành trong vòng 7 ngày.</li>
    <li>Sản phẩm phải còn nguyên hộp, đầy đủ phụ kiện.</li>
    <li>Hoàn tiền qua chuyển khoản hoặc ví VNPay trong 3–5 ngày làm việc.</li>
</ul>

<h3>4. Quyền sở hữu trí tuệ</h3>
<p>Toàn bộ nội dung, hình ảnh, logo trên website thuộc quyền sở hữu của ShopDemo. Nghiêm cấm sao chép dưới mọi hình thức.</p>

<h3>5. Thay đổi điều khoản</h3>
<p>ShopDemo có quyền cập nhật điều khoản bất kỳ lúc nào. Phiên bản mới có hiệu lực ngay khi đăng tải.</p>
HTML,
                'is_active' => true,
            ],
            [
                'slug' => 'bao-mat',
                'title' => 'Chính sách bảo mật',
                'meta_title' => 'Chính sách bảo mật — ShopDemo',
                'meta_description' => 'Chính sách bảo mật thông tin khách hàng của ShopDemo.',
                'show_in_footer' => true,
                'footer_sort' => 3,
                'body' => <<<'HTML'
<h2>Chính sách bảo mật</h2>
<p>ShopDemo cam kết bảo vệ thông tin cá nhân của khách hàng theo quy định của pháp luật Việt Nam.</p>

<h3>1. Thông tin thu thập</h3>
<ul>
    <li>Họ tên, số điện thoại, email.</li>
    <li>Địa chỉ giao hàng.</li>
    <li>Lịch sử mua hàng và hành vi duyệt web.</li>
</ul>

<h3>2. Mục đích sử dụng</h3>
<ul>
    <li>Xử lý đơn hàng và giao hàng.</li>
    <li>Hỗ trợ khách hàng, giải quyết khiếu nại.</li>
    <li>Gửi thông báo khuyến mãi (nếu khách hàng đồng ý).</li>
</ul>

<h3>3. Bảo mật thông tin</h3>
<ul>
    <li>Mật khẩu được mã hóa một chiều bằng thuật toán bcrypt.</li>
    <li>Kết nối HTTPS toàn site.</li>
    <li>Không chia sẻ thông tin cho bên thứ ba ngoại trừ đơn vị vận chuyển.</li>
</ul>

<h3>4. Quyền của khách hàng</h3>
<ul>
    <li>Yêu cầu xem, chỉnh sửa hoặc xoá thông tin cá nhân.</li>
    <li>Từ chối nhận email marketing.</li>
</ul>

<h3>5. Cookie</h3>
<p>Website sử dụng cookie để duy trì phiên đăng nhập và giỏ hàng. Bạn có thể tắt cookie trong trình duyệt, tuy nhiên một số chức năng có thể không hoạt động.</p>
HTML,
                'is_active' => true,
            ],
        ];

        foreach ($pages as $data) {
            Page::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('DemoPageSeeder: đã seed '.count($pages).' trang tĩnh.');
    }
}
