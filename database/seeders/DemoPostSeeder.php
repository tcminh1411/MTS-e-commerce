<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCategory;
// Tạo các bài viết minh họa.

use Illuminate\Database\Seeder;

class DemoPostSeeder extends Seeder
{
    public function run(): void
    {
        $cat = PostCategory::pluck('id', 'slug')->all();

        // Helper tạo body HTML
        $makeBody = function (string $intro, array $sections): string {
            $html = '<p>'.$intro.'</p>';
            foreach ($sections as $s) {
                $html .= "\n<h3>".$s['h'].'</h3>';
                if (! empty($s['p'])) {
                    $html .= "\n<p>".$s['p'].'</p>';
                }
                if (! empty($s['ul'])) {
                    $html .= "\n<ul>";
                    foreach ($s['ul'] as $li) {
                        $html .= "\n    <li>".$li.'</li>';
                    }
                    $html .= "\n</ul>";
                }
            }

            return $html;
        };

        $now = now();

        $posts = [
            [
                'slug' => 'top-5-smartphone-2026',
                'title' => 'Top 5 smartphone đáng mua nhất năm 2026',
                'excerpt' => 'Tổng hợp 5 mẫu smartphone nổi bật với hiệu năng mạnh, camera đỉnh và giá hợp lý trong năm 2026.',
                'post_category_id' => $cat['danh-gia-san-pham'] ?? null,
                'thumbnail' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&h=450&fit=crop&q=80',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => $now->copy()->subDays(1),
                'body' => $makeBody(
                    'Năm 2026 chứng kiến sự bùng nổ của nhiều mẫu smartphone với cấu hình mạnh mẽ và mức giá cạnh tranh. Dưới đây là 5 lựa chọn đáng chú ý nhất.',
                    [
                        ['h' => '1. iPhone 16 Pro Max', 'p' => 'Chip A18 Pro, camera 48MP, màn hình ProMotion 120Hz.'],
                        ['h' => '2. Samsung Galaxy S26 Ultra', 'p' => 'S-Pen tích hợp, camera 200MP, pin 5500mAh.'],
                        ['h' => '3. Xiaomi 15 Pro', 'p' => 'Snapdragon 8 Gen 4, sạc nhanh 120W.'],
                        ['h' => '4. OPPO Find X8 Pro', 'p' => 'Camera Hasselblad, thiết kế mỏng nhẹ.'],
                        ['h' => '5. Google Pixel 10 Pro', 'p' => 'Android thuần, AI tiên tiến, camera ấn tượng.'],
                    ]
                ),
            ],
            [
                'slug' => 'danh-gia-iphone-16-pro-max',
                'title' => 'Đánh giá chi tiết iPhone 16 Pro Max sau 1 tháng sử dụng',
                'excerpt' => 'Trải nghiệm thực tế iPhone 16 Pro Max: hiệu năng, camera, pin và những điểm cần cân nhắc.',
                'post_category_id' => $cat['danh-gia-san-pham'] ?? null,
                'thumbnail' => 'https://images.unsplash.com/photo-1592286927505-1def25115558?w=800&h=450&fit=crop&q=80',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => $now->copy()->subDays(3),
                'body' => $makeBody(
                    'iPhone 16 Pro Max là flagship mới nhất của Apple. Sau 1 tháng dùng thực tế, mình có một số đánh giá khách quan.',
                    [
                        ['h' => 'Thiết kế', 'p' => 'Khung titan, màn hình 6.9 inch, trọng lượng 227g.'],
                        ['h' => 'Hiệu năng', 'p' => 'Chip A18 Pro xử lý mọi tác vụ mượt mà, gaming ổn định.'],
                        ['h' => 'Camera', 'p' => 'Camera chính 48MP, tele 5x, chụp đêm cực tốt.'],
                        ['h' => 'Pin', 'ul' => ['Dùng hỗn hợp: 1.5 ngày', 'Gaming liên tục: 7-8 tiếng', 'Sạc nhanh 45W: 50% trong 30 phút']],
                        ['h' => 'Kết luận', 'p' => 'Đáng mua nếu bạn cần flagship toàn diện. Giá hơi cao so với mặt bằng chung.'],
                    ]
                ),
            ],
            [
                'slug' => 'cach-bao-quan-pin-laptop',
                'title' => '5 cách bảo quản pin laptop luôn bền như mới',
                'excerpt' => 'Hướng dẫn chi tiết cách sạc, bảo quản pin laptop để kéo dài tuổi thọ lên đến 5 năm.',
                'post_category_id' => $cat['huong-dan'] ?? null,
                'thumbnail' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&h=450&fit=crop&q=80',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => $now->copy()->subDays(5),
                'body' => $makeBody(
                    'Pin laptop là bộ phận hao mòn nhanh nhất. Áp dụng 5 mẹo sau để pin luôn khỏe.',
                    [
                        ['h' => '1. Không sạc đến 100% liên tục', 'p' => 'Giữ pin trong khoảng 20-80% là tốt nhất cho tuổi thọ.'],
                        ['h' => '2. Tránh nhiệt độ cao', 'p' => 'Không để laptop trong xe hơi, gần nguồn nhiệt.'],
                        ['h' => '3. Dùng sạc chính hãng', 'p' => 'Sạc kém chất lượng làm pin chai nhanh.'],
                        ['h' => '4. Vệ sinh tản nhiệt định kỳ', 'p' => 'Bụi bám ở quạt làm máy nóng, ảnh hưởng pin.'],
                        ['h' => '5. Không vừa sạc vừa chơi game nặng', 'p' => 'Vừa sạc vừa tải nặng làm nhiệt tăng vọt, hại pin.'],
                    ]
                ),
            ],
            [
                'slug' => 'khuyen-mai-thang-10',
                'title' => 'Khuyến mãi tháng 10: Giảm đến 30% toàn bộ phụ kiện',
                'excerpt' => 'Chương trình ưu đãi lớn nhất năm — giảm giá sốc cho toàn bộ phụ kiện công nghệ.',
                'post_category_id' => $cat['khuyen-mai'] ?? null,
                'thumbnail' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=800&h=450&fit=crop&q=80',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => $now->copy()->subDays(7),
                'body' => $makeBody(
                    'Nhân dịp tháng 10, ShopDemo triển khai chương trình khuyến mãi lớn với hàng ngàn sản phẩm giảm giá.',
                    [
                        ['h' => 'Ưu đãi chính', 'ul' => ['Giảm 30% toàn bộ phụ kiện', 'Giảm 20% sạc dự phòng Anker', 'Giảm 15% tai nghe Sony, JBL', 'Freeship cho đơn từ 500k']],
                        ['h' => 'Thời gian', 'p' => 'Từ 01/10 đến hết 31/10/2026.'],
                        ['h' => 'Điều kiện', 'p' => 'Áp dụng cho tất cả khách hàng, không giới hạn số lượng.'],
                    ]
                ),
            ],
            [
                'slug' => 'so-sanh-windows-macos',
                'title' => 'So sánh Windows và macOS 2026: Nên chọn hệ điều hành nào?',
                'excerpt' => 'Phân tích ưu nhược điểm của Windows 12 và macOS 16 để giúp bạn chọn đúng hệ điều hành.',
                'post_category_id' => $cat['tin-cong-nghe'] ?? null,
                'thumbnail' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&h=450&fit=crop&q=80',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => $now->copy()->subDays(10),
                'body' => $makeBody(
                    'Windows và macOS luôn là cuộc chiến không hồi kết. Năm 2026, cả hai đều có những cải tiến đáng chú ý.',
                    [
                        ['h' => 'Windows 12', 'ul' => ['Hỗ trợ đa dạng phần cứng', 'Tương thích game tốt hơn', 'Miễn phí cập nhật', 'Nhược: bảo mật kém hơn']],
                        ['h' => 'macOS 16', 'ul' => ['Ổn định, bảo mật cao', 'Tối ưu cho sáng tạo nội dung', 'Nhược: giá máy cao', 'Nhược: ít game']],
                        ['h' => 'Kết luận', 'p' => 'Chọn Windows nếu bạn cần đa dụng + gaming. Chọn macOS nếu làm sáng tạo và cần ổn định.'],
                    ]
                ),
            ],
            [
                'slug' => 'meo-tiet-kiem-pin-dien-thoai',
                'title' => '7 mẹo tiết kiệm pin điện thoại cực hiệu quả',
                'excerpt' => 'Áp dụng 7 mẹo đơn giản sau để tăng thời lượng pin điện thoại lên 30-50%.',
                'post_category_id' => $cat['huong-dan'] ?? null,
                'thumbnail' => 'https://images.unsplash.com/photo-1580910051074-3eb694886505?w=800&h=450&fit=crop&q=80',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => $now->copy()->subDays(12),
                'body' => $makeBody(
                    'Pin điện thoại ngày càng lớn nhưng vẫn không đủ cho nhu cầu sử dụng. 7 mẹo sau sẽ giúp bạn.',
                    [
                        ['h' => 'Danh sách mẹo', 'ul' => [
                            'Giảm độ sáng màn hình',
                            'Tắt Bluetooth/WiFi khi không dùng',
                            'Bật chế độ tiết kiệm pin',
                            'Hạn chế ứng dụng chạy nền',
                            'Tắt thông báo không cần thiết',
                            'Dùng dark mode',
                            'Tránh sạc qua đêm',
                        ]],
                    ]
                ),
            ],
            [
                'slug' => 'top-tai-nghe-bluetooth-2026',
                'title' => 'Top 5 tai nghe Bluetooth tốt nhất 2026',
                'excerpt' => 'Điểm danh 5 mẫu tai nghe Bluetooth có chất âm hay, chống ồn tốt và pin trâu.',
                'post_category_id' => $cat['danh-gia-san-pham'] ?? null,
                'thumbnail' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&h=450&fit=crop&q=80',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => $now->copy()->subDays(15),
                'body' => $makeBody(
                    'Tai nghe Bluetooth đang ngày càng phổ biến. Đây là 5 lựa chọn tốt nhất năm 2026.',
                    [
                        ['h' => 'Top 5', 'ul' => [
                            'Sony WF-1000XM6 — Chống ồn tốt nhất',
                            'Apple AirPods Pro 3 — Kết nối tốt cho iPhone',
                            'Bose QuietComfort Ultra — Âm thanh cân bằng',
                            'Sennheiser Momentum 4 — Chất âm chi tiết',
                            'JBL Tour Pro 3 — Pin trâu, giá tốt',
                        ]],
                    ]
                ),
            ],
            [
                'slug' => 'tin-cong-nghe-apple-ra-mat-iphone-17',
                'title' => 'Apple chính thức ra mắt iPhone 17 series',
                'excerpt' => 'Apple vừa công bố iPhone 17 series với nhiều nâng cấp về camera và hiệu năng.',
                'post_category_id' => $cat['tin-cong-nghe'] ?? null,
                'thumbnail' => 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?w=800&h=450&fit=crop&q=80',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => $now->copy()->subDays(18),
                'body' => $makeBody(
                    'Sự kiện Apple tháng 9 vừa qua đã chính thức công bố iPhone 17 series với 4 phiên bản.',
                    [
                        ['h' => 'Điểm nổi bật', 'ul' => ['Chip A19', 'Camera 48MP toàn bộ', 'Màn hình 120Hz Pro', 'Sạc nhanh 60W']],
                        ['h' => 'Ngày bán ra', 'p' => 'Dự kiến 22/09/2026.'],
                    ]
                ),
            ],
            [
                'slug' => 'cach-kiem-tra-hang-chinh-hang',
                'title' => 'Cách kiểm tra hàng chính hãng khi mua online',
                'excerpt' => 'Hướng dẫn chi tiết cách kiểm tra sản phẩm chính hãng trước khi nhận hàng.',
                'post_category_id' => $cat['huong-dan'] ?? null,
                'thumbnail' => 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=800&h=450&fit=crop&q=80',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => $now->copy()->subDays(20),
                'body' => $makeBody(
                    'Mua online tiện lợi nhưng cũng tiềm ẩn rủi ro hàng giả. Đây là cách kiểm tra.',
                    [
                        ['h' => 'Các bước kiểm tra', 'ul' => [
                            'Check mã IMEI/serial trên trang chủ hãng',
                            'Kiểm tra seal hộp còn nguyên vẹn',
                            'Xem phiếu bảo hành chính hãng',
                            'Đối chiếu ảnh với sản phẩm thực tế',
                            'Test máy trước khi thanh toán COD',
                        ]],
                    ]
                ),
            ],
            [
                'slug' => 'du-doan-cong-nghe-2027',
                'title' => 'Dự đoán xu hướng công nghệ nổi bật năm 2027',
                'excerpt' => 'AI, AR, xe điện tự lái — đâu là xu hướng công nghệ sẽ bùng nổ năm 2027?',
                'post_category_id' => $cat['tin-cong-nghe'] ?? null,
                'thumbnail' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&h=450&fit=crop&q=80',
                'status' => Post::STATUS_DRAFT,
                'published_at' => null,
                'body' => $makeBody(
                    'Năm 2027 hứa hẹn nhiều đột phá công nghệ. Dưới đây là dự đoán của mình.',
                    [
                        ['h' => 'AI tích hợp mọi nơi', 'p' => 'AI sẽ thâm nhập sâu hơn vào đời sống.'],
                        ['h' => 'AR kính thông minh', 'p' => 'Apple Vision Pro thế hệ 3, Meta Quest 4.'],
                        ['h' => 'Xe điện tự lái', 'p' => 'Tesla, VinFast sẽ ra mắt nhiều mẫu mới.'],
                    ]
                ),
            ],
        ];

        // B5: inject view_count cho published posts (chưa có data thật)
        foreach ($posts as &$p) {
            if (! isset($p['view_count'])) {
                $p['view_count'] = $p['status'] === Post::STATUS_PUBLISHED
                    ? random_int(100, 5000)
                    : 0;
            }
        }
        unset($p);

        foreach ($posts as $data) {
            // Preserve view_count đã tích luỹ (không reset khi re-seed)
            $existing = Post::where('slug', $data['slug'])->first();
            if ($existing && $existing->view_count > 0) {
                $data['view_count'] = $existing->view_count;
            }

            Post::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $published = collect($posts)->where('status', Post::STATUS_PUBLISHED)->count();
        $draft = collect($posts)->where('status', Post::STATUS_DRAFT)->count();

        $totalViews = collect($posts)->sum('view_count');
        $this->command->info('DemoPostSeeder: đã seed '.count($posts)." bài viết ($published published, $draft draft) — total view: ".number_format($totalViews));
    }
}
