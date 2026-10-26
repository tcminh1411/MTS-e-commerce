<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
// Tạo dữ liệu sản phẩm và biến thể minh họa.

use Illuminate\Database\Seeder;

class DemoProductSeeder extends Seeder
{
    public function run(): void
    {
        $catMap = Category::pluck('id', 'slug');
        $brandMap = Brand::pluck('id', 'slug');

        // Mỗi item: name, slug, category_slug, brand_slug, price, sale_price,
        // is_featured, img (keyword LoremFlickr), short_description, variants[]
        $products = [
            // ============ SMARTPHONE ============
            [
                'name' => 'iPhone 15 Pro Max 256GB', 'slug' => 'iphone-15-pro-max-256gb',
                'category' => 'smartphone', 'brand' => 'apple',
                'price' => 34990000, 'sale_price' => 32990000, 'is_featured' => true, 'img' => 'iphone',
                'short_description' => 'iPhone 15 Pro Max — khung Titan, chip A17 Pro, camera 48MP, USB-C.',
                'variants' => [
                    ['name' => 'Titan Tự Nhiên 256GB', 'price' => 34990000, 'sale_price' => 32990000],
                    ['name' => 'Titan Xanh 256GB',     'price' => 34990000, 'sale_price' => 32990000],
                    ['name' => 'Titan Tự Nhiên 512GB', 'price' => 40990000, 'sale_price' => 38990000],
                    ['name' => 'Titan Xanh 512GB',     'price' => 40990000, 'sale_price' => 38990000],
                ],
            ],
            [
                'name' => 'iPhone 15 128GB', 'slug' => 'iphone-15-128gb',
                'category' => 'smartphone', 'brand' => 'apple',
                'price' => 22990000, 'sale_price' => 20990000, 'is_featured' => true, 'img' => 'iphone',
                'short_description' => 'iPhone 15 — Dynamic Island, camera 48MP, chip A16 Bionic.',
                'variants' => [
                    ['name' => 'Đen 128GB',  'price' => 22990000, 'sale_price' => 20990000],
                    ['name' => 'Xanh 128GB', 'price' => 22990000, 'sale_price' => 20990000],
                    ['name' => 'Đen 256GB',  'price' => 25990000, 'sale_price' => 23990000],
                    ['name' => 'Xanh 256GB', 'price' => 25990000, 'sale_price' => 23990000],
                ],
            ],
            [
                'name' => 'iPhone 14 128GB', 'slug' => 'iphone-14-128gb',
                'category' => 'smartphone', 'brand' => 'apple',
                'price' => 18990000, 'sale_price' => 16990000, 'is_featured' => false, 'img' => 'iphone',
                'short_description' => 'iPhone 14 — chip A15 Bionic, camera kép 12MP.',
                'variants' => [
                    ['name' => 'Đen 128GB',   'price' => 18990000, 'sale_price' => 16990000],
                    ['name' => 'Trắng 128GB', 'price' => 18990000, 'sale_price' => 16990000],
                ],
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra 256GB', 'slug' => 'samsung-galaxy-s24-ultra-256gb',
                'category' => 'smartphone', 'brand' => 'samsung',
                'price' => 31990000, 'sale_price' => 28990000, 'is_featured' => true, 'img' => 'samsung',
                'short_description' => 'Galaxy S24 Ultra — S Pen, camera 200MP, Galaxy AI.',
                'variants' => [
                    ['name' => 'Titan Đen 256GB', 'price' => 31990000, 'sale_price' => 28990000],
                    ['name' => 'Titan Tím 256GB', 'price' => 31990000, 'sale_price' => 28990000],
                    ['name' => 'Titan Đen 512GB', 'price' => 36990000, 'sale_price' => 33990000],
                    ['name' => 'Titan Tím 512GB', 'price' => 36990000, 'sale_price' => 33990000],
                ],
            ],
            [
                'name' => 'Samsung Galaxy S24 256GB', 'slug' => 'samsung-galaxy-s24-256gb',
                'category' => 'smartphone', 'brand' => 'samsung',
                'price' => 22990000, 'sale_price' => 20490000, 'is_featured' => false, 'img' => 'samsung',
                'short_description' => 'Galaxy S24 — màn hình 6.2", chip Exynos 2400, Galaxy AI.',
                'variants' => [
                    ['name' => 'Đen 256GB',    'price' => 22990000, 'sale_price' => 20490000],
                    ['name' => 'Xanh lá 256GB', 'price' => 22990000, 'sale_price' => 20490000],
                ],
            ],
            [
                'name' => 'Samsung Galaxy A55 5G 128GB', 'slug' => 'samsung-galaxy-a55-5g-128gb',
                'category' => 'smartphone', 'brand' => 'samsung',
                'price' => 9990000, 'sale_price' => 8990000, 'is_featured' => false, 'img' => 'samsung',
                'short_description' => 'Galaxy A55 5G — camera 50MP OIS, pin 5000mAh, sạc nhanh 25W.',
                'variants' => [
                    ['name' => 'Xanh 128GB', 'price' => 9990000, 'sale_price' => 8990000],
                    ['name' => 'Đen 128GB',  'price' => 9990000, 'sale_price' => 8990000],
                ],
            ],
            [
                'name' => 'Xiaomi 14 256GB', 'slug' => 'xiaomi-14-256gb',
                'category' => 'smartphone', 'brand' => 'xiaomi',
                'price' => 18990000, 'sale_price' => 17490000, 'is_featured' => true, 'img' => 'xiaomi',
                'short_description' => 'Xiaomi 14 — Snapdragon 8 Gen 3, camera Leica, sạc 90W.',
                'variants' => [
                    ['name' => 'Đen 256GB',   'price' => 18990000, 'sale_price' => 17490000],
                    ['name' => 'Trắng 256GB', 'price' => 18990000, 'sale_price' => 17490000],
                    ['name' => 'Đen 512GB',   'price' => 21990000, 'sale_price' => 19990000],
                    ['name' => 'Trắng 512GB', 'price' => 21990000, 'sale_price' => 19990000],
                ],
            ],
            [
                'name' => 'Redmi Note 13 Pro 128GB', 'slug' => 'redmi-note-13-pro-128gb',
                'category' => 'smartphone', 'brand' => 'xiaomi',
                'price' => 6490000, 'sale_price' => 5990000, 'is_featured' => false, 'img' => 'xiaomi',
                'short_description' => 'Redmi Note 13 Pro — camera 200MP, màn AMOLED 120Hz.',
                'variants' => [
                    ['name' => 'Đen 128GB', 'price' => 6490000, 'sale_price' => 5990000],
                    ['name' => 'Tím 128GB', 'price' => 6490000, 'sale_price' => 5990000],
                ],
            ],
            [
                'name' => 'OPPO Reno11 5G 256GB', 'slug' => 'oppo-reno11-5g-256gb',
                'category' => 'smartphone', 'brand' => 'oppo',
                'price' => 10990000, 'sale_price' => 9990000, 'is_featured' => false, 'img' => 'android-phone',
                'short_description' => 'OPPO Reno11 5G — camera chân dung 32MP, sạc SuperVOOC 67W.',
                'variants' => [
                    ['name' => 'Xanh 256GB', 'price' => 10990000, 'sale_price' => 9990000],
                    ['name' => 'Đen 256GB',  'price' => 10990000, 'sale_price' => 9990000],
                ],
            ],
            [
                'name' => 'Realme 12 Pro+ 5G 256GB', 'slug' => 'realme-12-pro-plus-5g-256gb',
                'category' => 'smartphone', 'brand' => 'realme',
                'price' => 10990000, 'sale_price' => 9490000, 'is_featured' => false, 'img' => 'android-phone',
                'short_description' => 'Realme 12 Pro+ — camera periscope 64MP, Snapdragon 7s Gen 2.',
                'variants' => [
                    ['name' => 'Xanh 256GB', 'price' => 10990000, 'sale_price' => 9490000],
                    ['name' => 'Be 256GB',   'price' => 10990000, 'sale_price' => 9490000],
                ],
            ],

            // ============ ĐIỆN THOẠI GAMING ============
            [
                'name' => 'Asus ROG Phone 7 Ultimate', 'slug' => 'asus-rog-phone-7-ultimate',
                'category' => 'dien-thoai-gaming', 'brand' => 'asus',
                'price' => 26990000, 'sale_price' => 24990000, 'is_featured' => false, 'img' => 'gaming-phone',
                'short_description' => 'ROG Phone 7 Ultimate — Snapdragon 8 Gen 2, màn AMOLED 165Hz.',
                'variants' => [],
            ],
            [
                'name' => 'RedMagic 9 Pro 256GB', 'slug' => 'redmagic-9-pro-256gb',
                'category' => 'dien-thoai-gaming', 'brand' => 'vivo',
                'price' => 19990000, 'sale_price' => 18490000, 'is_featured' => false, 'img' => 'gaming-phone',
                'short_description' => 'RedMagic 9 Pro — tản nhiệt quạt, Snapdragon 8 Gen 3.',
                'variants' => [],
            ],

            // ============ ĐIỆN THOẠI PHỔ THÔNG ============
            [
                'name' => 'Nokia 105 2023', 'slug' => 'nokia-105-2023',
                'category' => 'dien-thoai-pho-thong', 'brand' => 'samsung',
                'price' => 490000, 'sale_price' => null, 'is_featured' => false, 'img' => 'feature-phone',
                'short_description' => 'Nokia 105 — pin trâu 800mAh, nghe gọi cơ bản.',
                'variants' => [],
            ],

            // ============ LAPTOP GAMING ============
            [
                'name' => 'Asus ROG Strix G16 i7 RTX 4060', 'slug' => 'asus-rog-strix-g16-i7-rtx4060',
                'category' => 'laptop-gaming', 'brand' => 'asus',
                'price' => 34990000, 'sale_price' => 32990000, 'is_featured' => true, 'img' => 'gaming-laptop',
                'short_description' => 'ROG Strix G16 — i7-13650HX, RTX 4060 8GB, màn 165Hz.',
                'variants' => [
                    ['name' => 'i7 / 16GB / 512GB', 'price' => 34990000, 'sale_price' => 32990000],
                    ['name' => 'i7 / 32GB / 1TB',   'price' => 40990000, 'sale_price' => 38990000],
                ],
            ],
            [
                'name' => 'MSI Katana 15 i7 RTX 4060', 'slug' => 'msi-katana-15-i7-rtx4060',
                'category' => 'laptop-gaming', 'brand' => 'msi',
                'price' => 27990000, 'sale_price' => 25990000, 'is_featured' => false, 'img' => 'gaming-laptop',
                'short_description' => 'MSI Katana 15 — i7-13620H, RTX 4060, màn 144Hz.',
                'variants' => [
                    ['name' => 'i7 / 16GB / 512GB', 'price' => 27990000, 'sale_price' => 25990000],
                    ['name' => 'i7 / 32GB / 1TB',   'price' => 32990000, 'sale_price' => 30990000],
                ],
            ],
            [
                'name' => 'Acer Nitro V15 Ryzen 7 RTX 4050', 'slug' => 'acer-nitro-v15-ryzen7-rtx4050',
                'category' => 'laptop-gaming', 'brand' => 'acer',
                'price' => 22990000, 'sale_price' => 20990000, 'is_featured' => false, 'img' => 'gaming-laptop',
                'short_description' => 'Acer Nitro V15 — Ryzen 7 7735HS, RTX 4050, màn 144Hz.',
                'variants' => [
                    ['name' => 'R7 / 8GB / 512GB',  'price' => 22990000, 'sale_price' => 20990000],
                    ['name' => 'R7 / 16GB / 512GB', 'price' => 25490000, 'sale_price' => 23490000],
                ],
            ],
            [
                'name' => 'Lenovo Legion 5 Pro RTX 4070', 'slug' => 'lenovo-legion-5-pro-rtx4070',
                'category' => 'laptop-gaming', 'brand' => 'lenovo',
                'price' => 44990000, 'sale_price' => 41990000, 'is_featured' => true, 'img' => 'gaming-laptop',
                'short_description' => 'Legion 5 Pro — Ryzen 7 7745HX, RTX 4070, màn 2.5K 165Hz.',
                'variants' => [
                    ['name' => 'R7 / 16GB / 1TB', 'price' => 44990000, 'sale_price' => 41990000],
                    ['name' => 'R9 / 32GB / 1TB', 'price' => 52990000, 'sale_price' => 49990000],
                ],
            ],

            // ============ LAPTOP VĂN PHÒNG ============
            [
                'name' => 'Dell Inspiron 15 3520 i5', 'slug' => 'dell-inspiron-15-3520-i5',
                'category' => 'laptop-van-phong', 'brand' => 'dell',
                'price' => 14990000, 'sale_price' => 13490000, 'is_featured' => false, 'img' => 'laptop',
                'short_description' => 'Dell Inspiron 15 3520 — i5-1235U, RAM 8GB, SSD 512GB.',
                'variants' => [
                    ['name' => 'i5 / 8GB / 512GB',  'price' => 14990000, 'sale_price' => 13490000],
                    ['name' => 'i5 / 16GB / 512GB', 'price' => 16490000, 'sale_price' => 14990000],
                    ['name' => 'i7 / 16GB / 1TB',   'price' => 19990000, 'sale_price' => 18490000],
                ],
            ],
            [
                'name' => 'HP Pavilion 15 i5 1235U', 'slug' => 'hp-pavilion-15-i5-1235u',
                'category' => 'laptop-van-phong', 'brand' => 'hp',
                'price' => 15990000, 'sale_price' => 14490000, 'is_featured' => false, 'img' => 'laptop',
                'short_description' => 'HP Pavilion 15 — i5-1235U, RAM 8GB, SSD 512GB, màn 15.6" FHD.',
                'variants' => [
                    ['name' => 'i5 / 8GB / 512GB',  'price' => 15990000, 'sale_price' => 14490000],
                    ['name' => 'i5 / 16GB / 512GB', 'price' => 17490000, 'sale_price' => 15990000],
                ],
            ],
            [
                'name' => 'Lenovo IdeaPad Slim 3 Ryzen 5', 'slug' => 'lenovo-ideapad-slim-3-ryzen5',
                'category' => 'laptop-van-phong', 'brand' => 'lenovo',
                'price' => 12990000, 'sale_price' => 11490000, 'is_featured' => false, 'img' => 'laptop',
                'short_description' => 'IdeaPad Slim 3 — Ryzen 5 7530U, RAM 16GB, SSD 512GB.',
                'variants' => [
                    ['name' => 'R5 / 8GB / 512GB',  'price' => 12990000, 'sale_price' => 11490000],
                    ['name' => 'R5 / 16GB / 512GB', 'price' => 14490000, 'sale_price' => 12990000],
                ],
            ],
            [
                'name' => 'Asus Vivobook 15 OLED i5', 'slug' => 'asus-vivobook-15-oled-i5',
                'category' => 'laptop-van-phong', 'brand' => 'asus',
                'price' => 16990000, 'sale_price' => 15490000, 'is_featured' => false, 'img' => 'laptop',
                'short_description' => 'Vivobook 15 OLED — i5-13500H, màn OLED FHD, RAM 16GB.',
                'variants' => [
                    ['name' => 'i5 / 8GB / 512GB',  'price' => 16990000, 'sale_price' => 15490000],
                    ['name' => 'i5 / 16GB / 512GB', 'price' => 18490000, 'sale_price' => 16990000],
                ],
            ],
            [
                'name' => 'Dell XPS 13 Plus i7', 'slug' => 'dell-xps-13-plus-i7',
                'category' => 'laptop-van-phong', 'brand' => 'dell',
                'price' => 42990000, 'sale_price' => 39990000, 'is_featured' => true, 'img' => 'laptop',
                'short_description' => 'Dell XPS 13 Plus — i7-1360P, màn 13.4" 3.5K OLED, siêu mỏng.',
                'variants' => [
                    ['name' => 'i7 / 16GB / 512GB', 'price' => 42990000, 'sale_price' => 39990000],
                    ['name' => 'i7 / 32GB / 1TB',   'price' => 49990000, 'sale_price' => 46990000],
                ],
            ],

            // ============ MACBOOK ============
            [
                'name' => 'MacBook Pro 14 M3 2023', 'slug' => 'macbook-pro-14-m3-2023',
                'category' => 'macbook', 'brand' => 'apple',
                'price' => 49990000, 'sale_price' => 46990000, 'is_featured' => true, 'img' => 'macbook',
                'short_description' => 'MacBook Pro 14 — chip M3 Pro/Max, màn Liquid Retina XDR.',
                'variants' => [
                    ['name' => 'M3 Pro 18GB/512GB', 'price' => 49990000, 'sale_price' => 46990000],
                    ['name' => 'M3 Max 36GB/1TB',   'price' => 64990000, 'sale_price' => 61990000],
                ],
            ],
            [
                'name' => 'MacBook Air 13 M2 2023', 'slug' => 'macbook-air-13-m2-2023',
                'category' => 'macbook', 'brand' => 'apple',
                'price' => 27990000, 'sale_price' => 25990000, 'is_featured' => true, 'img' => 'macbook',
                'short_description' => 'MacBook Air 13 — chip M2, màn Liquid Retina, mỏng nhẹ.',
                'variants' => [
                    ['name' => 'M2 / 8GB / 256GB',  'price' => 27990000, 'sale_price' => 25990000],
                    ['name' => 'M2 / 8GB / 512GB',  'price' => 32990000, 'sale_price' => 30990000],
                    ['name' => 'M2 / 16GB / 512GB', 'price' => 35990000, 'sale_price' => 33990000],
                ],
            ],

            // ============ iPAD ============
            [
                'name' => 'iPad Pro 11 M2 2022', 'slug' => 'ipad-pro-11-m2-2022',
                'category' => 'ipad', 'brand' => 'apple',
                'price' => 23990000, 'sale_price' => 21990000, 'is_featured' => true, 'img' => 'ipad',
                'short_description' => 'iPad Pro 11 — chip M2, màn Liquid Retina, hỗ trợ Apple Pencil 2.',
                'variants' => [
                    ['name' => 'WiFi 128GB',       'price' => 23990000, 'sale_price' => 21990000],
                    ['name' => 'WiFi 256GB',       'price' => 26990000, 'sale_price' => 24990000],
                    ['name' => 'WiFi 512GB',       'price' => 32990000, 'sale_price' => 30990000],
                ],
            ],
            [
                'name' => 'iPad Air 5 M1 2022', 'slug' => 'ipad-air-5-m1-2022',
                'category' => 'ipad', 'brand' => 'apple',
                'price' => 15990000, 'sale_price' => 14490000, 'is_featured' => false, 'img' => 'ipad',
                'short_description' => 'iPad Air 5 — chip M1, màn 10.9" Liquid Retina.',
                'variants' => [
                    ['name' => 'WiFi 64GB',  'price' => 15990000, 'sale_price' => 14490000],
                    ['name' => 'WiFi 256GB', 'price' => 19990000, 'sale_price' => 18490000],
                ],
            ],
            [
                'name' => 'iPad 10.9 2022', 'slug' => 'ipad-10-9-2022',
                'category' => 'ipad', 'brand' => 'apple',
                'price' => 10990000, 'sale_price' => 9990000, 'is_featured' => false, 'img' => 'ipad',
                'short_description' => 'iPad 10.9 — chip A14 Bionic, thiết kế viền mỏng.',
                'variants' => [
                    ['name' => 'WiFi 64GB',  'price' => 10990000, 'sale_price' => 9990000],
                    ['name' => 'WiFi 256GB', 'price' => 13990000, 'sale_price' => 12990000],
                ],
            ],

            // ============ ANDROID TABLET ============
            [
                'name' => 'Samsung Galaxy Tab S9 11"', 'slug' => 'samsung-galaxy-tab-s9-11',
                'category' => 'android-tablet', 'brand' => 'samsung',
                'price' => 21990000, 'sale_price' => 19990000, 'is_featured' => false, 'img' => 'tablet',
                'short_description' => 'Galaxy Tab S9 — Snapdragon 8 Gen 2, kèm S Pen, màn AMOLED 120Hz.',
                'variants' => [
                    ['name' => 'WiFi 128GB', 'price' => 21990000, 'sale_price' => 19990000],
                    ['name' => 'WiFi 256GB', 'price' => 24990000, 'sale_price' => 22990000],
                ],
            ],
            [
                'name' => 'Xiaomi Pad 6 8GB/256GB', 'slug' => 'xiaomi-pad-6-8gb-256gb',
                'category' => 'android-tablet', 'brand' => 'xiaomi',
                'price' => 8990000, 'sale_price' => 7990000, 'is_featured' => false, 'img' => 'tablet',
                'short_description' => 'Xiaomi Pad 6 — Snapdragon 870, màn 11" 144Hz.',
                'variants' => [
                    ['name' => '6GB / 128GB', 'price' => 7990000, 'sale_price' => 6990000],
                    ['name' => '8GB / 256GB', 'price' => 8990000, 'sale_price' => 7990000],
                ],
            ],

            // ============ TAI NGHE ============
            [
                'name' => 'Tai nghe Sony WH-1000XM5', 'slug' => 'tai-nghe-sony-wh-1000xm5',
                'category' => 'tai-nghe', 'brand' => 'sony',
                'price' => 7990000, 'sale_price' => 6990000, 'is_featured' => true, 'img' => 'headphone',
                'short_description' => 'Sony WH-1000XM5 — chống ồn hàng đầu, pin 30h, Bluetooth 5.2.',
                'variants' => [],
            ],
            [
                'name' => 'Tai nghe JBL Tune 770NC', 'slug' => 'tai-nghe-jbl-tune-770nc',
                'category' => 'tai-nghe', 'brand' => 'jbl',
                'price' => 2490000, 'sale_price' => 2190000, 'is_featured' => false, 'img' => 'headphone',
                'short_description' => 'JBL Tune 770NC — chống ồn chủ động, pin 70h.',
                'variants' => [],
            ],

            // ============ SẠC & CÁP ============
            [
                'name' => 'Củ sạc Anker 65W GaN', 'slug' => 'cu-sac-anker-65w-gan',
                'category' => 'sac-cap', 'brand' => 'anker',
                'price' => 890000, 'sale_price' => 790000, 'is_featured' => false, 'img' => 'charger',
                'short_description' => 'Anker 65W GaN — 3 cổng, sạc nhanh laptop + điện thoại.',
                'variants' => [],
            ],
            [
                'name' => 'Pin dự phòng Baseus 10000mAh', 'slug' => 'pin-du-phong-baseus-10000mah',
                'category' => 'sac-cap', 'brand' => 'baseus',
                'price' => 490000, 'sale_price' => 390000, 'is_featured' => false, 'img' => 'power-bank',
                'short_description' => 'Baseus 10000mAh — sạc nhanh 22.5W, có sẵn 2 cáp.',
                'variants' => [],
            ],
            [
                'name' => 'Cáp USB-C to Lightning Anker 1m', 'slug' => 'cap-usbc-lightning-anker-1m',
                'category' => 'sac-cap', 'brand' => 'anker',
                'price' => 290000, 'sale_price' => null, 'is_featured' => false, 'img' => 'usb-cable',
                'short_description' => 'Cáp Anker USB-C to Lightning 1m — sạc nhanh PD, bọc dù bền.',
                'variants' => [],
            ],

            // ============ ỐP LƯNG ============
            [
                'name' => 'Ốp lưng iPhone 15 Pro Max Silicon Magsafe', 'slug' => 'op-lung-iphone-15-pro-max-silicon-magsafe',
                'category' => 'op-lung-bao-da', 'brand' => 'apple',
                'price' => 1490000, 'sale_price' => null, 'is_featured' => false, 'img' => 'phone-case',
                'short_description' => 'Ốp Silicon Magsafe chính hãng Apple — bám tốt, hỗ trợ sạc Magsafe.',
                'variants' => [],
            ],
            [
                'name' => 'Bao da iPad 10.9 có bàn phím', 'slug' => 'bao-da-ipad-10-9-co-ban-phim',
                'category' => 'op-lung-bao-da', 'brand' => 'logitech',
                'price' => 1290000, 'sale_price' => 1090000, 'is_featured' => false, 'img' => 'tablet-case',
                'short_description' => 'Bao da iPad 10.9 tích hợp bàn phím Bluetooth, gập 3 tư thế.',
                'variants' => [],
            ],

            // ============ CHUỘT & BÀN PHÍM ============
            [
                'name' => 'Chuột Logitech MX Master 3S', 'slug' => 'chuot-logitech-mx-master-3s',
                'category' => 'chuot-ban-phim', 'brand' => 'logitech',
                'price' => 2590000, 'sale_price' => 2390000, 'is_featured' => true, 'img' => 'computer-mouse',
                'short_description' => 'MX Master 3S — cảm biến 8000 DPI, cuộn MagSpeed, kết nối 3 thiết bị.',
                'variants' => [],
            ],
            [
                'name' => 'Chuột gaming Logitech G502 Hero', 'slug' => 'chuot-gaming-logitech-g502-hero',
                'category' => 'chuot-ban-phim', 'brand' => 'logitech',
                'price' => 1290000, 'sale_price' => 1090000, 'is_featured' => false, 'img' => 'gaming-mouse',
                'short_description' => 'G502 Hero — cảm biến HERO 25K, 11 nút lập trình.',
                'variants' => [],
            ],
            [
                'name' => 'Bàn phím cơ Razer BlackWidow V4', 'slug' => 'ban-phim-co-razer-blackwidow-v4',
                'category' => 'chuot-ban-phim', 'brand' => 'razer',
                'price' => 3990000, 'sale_price' => 3690000, 'is_featured' => false, 'img' => 'mechanical-keyboard',
                'short_description' => 'Razer BlackWidow V4 — switch Green, LED RGB Chroma, kê tay.',
                'variants' => [],
            ],

            // ============ LOA BLUETOOTH ============
            [
                'name' => 'Loa Bluetooth JBL Flip 6', 'slug' => 'loa-bluetooth-jbl-flip-6',
                'category' => 'loa-bluetooth', 'brand' => 'jbl',
                'price' => 2990000, 'sale_price' => 2690000, 'is_featured' => true, 'img' => 'bluetooth-speaker',
                'short_description' => 'JBL Flip 6 — chống nước IP67, pin 12h, âm thanh JBL Pro Sound.',
                'variants' => [],
            ],
            [
                'name' => 'Loa Bluetooth Sony SRS-XB100', 'slug' => 'loa-bluetooth-sony-srs-xb100',
                'category' => 'loa-bluetooth', 'brand' => 'sony',
                'price' => 1490000, 'sale_price' => 1290000, 'is_featured' => false, 'img' => 'bluetooth-speaker',
                'short_description' => 'Sony SRS-XB100 — loa nhỏ gọn, chống nước IP67, pin 16h.',
                'variants' => [],
            ],

            // ============ TWS ============
            [
                'name' => 'AirPods Pro 2 USB-C', 'slug' => 'airpods-pro-2-usb-c',
                'category' => 'tws', 'brand' => 'apple',
                'price' => 5990000, 'sale_price' => 5490000, 'is_featured' => true, 'img' => 'airpods',
                'short_description' => 'AirPods Pro 2 — chip H2, chống ồn chủ động, hộp sạc USB-C.',
                'variants' => [],
            ],
            [
                'name' => 'Samsung Galaxy Buds 2 Pro', 'slug' => 'samsung-galaxy-buds-2-pro',
                'category' => 'tws', 'brand' => 'samsung',
                'price' => 4490000, 'sale_price' => 3990000, 'is_featured' => false, 'img' => 'earbuds',
                'short_description' => 'Galaxy Buds 2 Pro — chống ồn ANC, âm thanh 24bit Hi-Fi.',
                'variants' => [],
            ],
            [
                'name' => 'Xiaomi Buds 4 Pro', 'slug' => 'xiaomi-buds-4-pro',
                'category' => 'tws', 'brand' => 'xiaomi',
                'price' => 1990000, 'sale_price' => 1790000, 'is_featured' => false, 'img' => 'earbuds',
                'short_description' => 'Xiaomi Buds 4 Pro — chống ồn 48dB, pin 38h, LDAC.',
                'variants' => [],
            ],

            // ============ SMARTWATCH ============
            [
                'name' => 'Apple Watch Series 9 GPS 41mm', 'slug' => 'apple-watch-series-9-gps-41mm',
                'category' => 'smartwatch', 'brand' => 'apple',
                'price' => 10990000, 'sale_price' => 9990000, 'is_featured' => true, 'img' => 'apple-watch',
                'short_description' => 'Apple Watch S9 — chip S9, màn hình 2000 nits, Double Tap.',
                'variants' => [],
            ],
            [
                'name' => 'Samsung Galaxy Watch 6 40mm', 'slug' => 'samsung-galaxy-watch-6-40mm',
                'category' => 'smartwatch', 'brand' => 'samsung',
                'price' => 6990000, 'sale_price' => 5990000, 'is_featured' => false, 'img' => 'smartwatch',
                'short_description' => 'Galaxy Watch 6 — theo dõi sức khoẻ toàn diện, Wear OS.',
                'variants' => [],
            ],
        ];

        $idx = 0;
        foreach ($products as $p) {
            $idx++;
            $skuBase = 'SP-'.str_pad((string) $idx, 4, '0', STR_PAD_LEFT);
            $isVariable = ! empty($p['variants']) && count($p['variants']) > 1;

            $product = Product::create([
                'sku' => $skuBase,
                'name' => $p['name'],
                'slug' => $p['slug'],
                'category_id' => $catMap[$p['category']] ?? null,
                'brand_id' => $brandMap[$p['brand']] ?? null,
                'product_type' => $isVariable ? 'variable' : 'simple',
                'short_description' => $p['short_description'],
                'description' => $this->makeDescription($p['category'], $p['short_description']),
                'price' => $p['price'],
                'sale_price' => $p['sale_price'] ?? null,
                'cost_price' => (int) round($p['price'] * 0.82),
                'currency' => 'VND',
                'weight_gram' => random_int(200, 1500),
                'status' => 'active',
                'is_featured' => (bool) ($p['is_featured'] ?? false),
                'published_at' => now()->subDays(random_int(0, 60)),
                'rating_avg' => 0,
                'rating_count' => 0,
                'sold_count' => random_int(10, 500),
                'meta_title' => $p['name'],
                'meta_description' => $p['short_description'],
            ]);

            // 3 ảnh LoremFlickr khớp keyword, lock unique để cố định
            for ($k = 1; $k <= 3; $k++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => $this->pickImage($p['img'], $idx, $k),
                    'alt' => $p['name'],
                    'sort_order' => $k - 1,
                    'is_primary' => $k === 1,
                ]);
            }

            // Variants
            $variants = ! empty($p['variants']) ? $p['variants'] : [[
                'name' => 'Mặc định',
                'price' => $p['price'],
                'sale_price' => $p['sale_price'] ?? null,
            ]];

            foreach ($variants as $j => $v) {
                $suffix = $isVariable
                    ? str_pad((string) ($j + 1), 2, '0', STR_PAD_LEFT)
                    : 'DEF';

                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $skuBase.'-'.$suffix,
                    'name' => $v['name'],
                    'price' => $v['price'],
                    'sale_price' => $v['sale_price'] ?? null,
                    'cost_price' => (int) round($v['price'] * 0.82),
                    'weight_gram' => random_int(200, 1500),
                    'is_default' => $j === 0,
                    'stock' => 0,
                    'status' => 'active',
                    'sort_order' => $j,
                ]);
            }
        }

        $this->command->info(sprintf(
            '✅ DemoProductSeeder: %d products (%d variable), %d variants, %d images.',
            Product::count(),
            Product::where('product_type', 'variable')->count(),
            ProductVariant::count(),
            ProductImage::count(),
        ));
    }

    /**
     * Map keyword → array Unsplash photo IDs (đã curate sẵn).
     * Unsplash CDN ổn định vĩnh viễn, không như loremflickr đã chặn public.
     */
    private const IMAGE_MAP = [
        'iphone' => [
            '1592286927505-1def25115558',
            '1511707171634-5f897ff02aa9',
            '1580910051074-3eb694886505',
            '1510557880182-3d4d3cba35a5',
            '1567581935884-3349723552ca',
        ],
        'samsung' => [
            '1610945415295-d9bbf067e59c',
            '1565849904461-04a58ad377e0',
            '1580910051074-3eb694886505',
            '1511707171634-5f897ff02aa9',
        ],
        'android-phone' => [
            '1598327105666-5b89351aff97',
            '1511707171634-5f897ff02aa9',
            '1580910051074-3eb694886505',
        ],
        'xiaomi' => [
            '1598327105666-5b89351aff97',
            '1511707171634-5f897ff02aa9',
            '1610945415295-d9bbf067e59c',
        ],
        'gaming-phone' => [
            '1542751371-adc38448a05e',
            '1552820728-8b83bb6b0513',
        ],
        'feature-phone' => [
            '1520923642038-b4259acecbd7',
            '1556656793-08538906a9f8',
        ],
        'laptop' => [
            '1496181133206-80ce9b88a853',
            '1517336714731-489689fd1ca8',
            '1541807084-5c52b6b3adef',
            '1588872657578-7efd1f1555ed',
        ],
        'gaming-laptop' => [
            '1587202372634-32705e3bf49c',
            '1603302576837-37561b2e2302',
            '1542751371-adc38448a05e',
        ],
        'macbook' => [
            '1517336714731-489689fd1ca8',
            '1541807084-5c52b6b3adef',
            '1496181133206-80ce9b88a853',
            '1611186871348-b1ce696e52c9',
        ],
        'ipad' => [
            '1544244015-0df4b3ffc6b0',
            '1561154464-82e9adf32764',
            '1585790050230-5dd28404ccb9',
        ],
        'tablet' => [
            '1585790050230-5dd28404ccb9',
            '1544244015-0df4b3ffc6b0',
            '1561154464-82e9adf32764',
        ],
        'headphone' => [
            '1505740420928-5e560c06d30e',
            '1487215078519-e21cc028cb29',
            '1546435770-a3e426bf472b',
            '1583394838336-acd977736f90',
        ],
        'airpods' => [
            '1600294037681-c80b4cb5b434',
            '1590658268037-6bf12165a8df',
            '1606220588913-b3aacb4d2f46',
        ],
        'earbuds' => [
            '1590658268037-6bf12165a8df',
            '1606220588913-b3aacb4d2f46',
            '1600294037681-c80b4cb5b434',
        ],
        'bluetooth-speaker' => [
            '1608043152269-423dbba4e7e1',
            '1545454675-3531b543be5d',
            '1589003077984-894e133dabab',
        ],
        'charger' => [
            '1583863788434-e58a36330cf0',
            '1609592806596-b43bada2f2e3',
            '1618410320928-25228d811631',
        ],
        'power-bank' => [
            '1609592806596-b43bada2f2e3',
            '1618410320928-25228d811631',
            '1583863788434-e58a36330cf0',
        ],
        'usb-cable' => [
            '1618410320928-25228d811631',
            '1583863788434-e58a36330cf0',
            '1609592806596-b43bada2f2e3',
        ],
        'phone-case' => [
            '1601784551446-20c9e07cdbdb',
            '1580910051074-3eb694886505',
        ],
        'tablet-case' => [
            '1544244015-0df4b3ffc6b0',
            '1561154464-82e9adf32764',
        ],
        'computer-mouse' => [
            '1527864550417-7fd91fc51a46',
            '1615663245857-ac93bb7c39e7',
            '1587829741301-dc798b83add3',
        ],
        'gaming-mouse' => [
            '1615663245857-ac93bb7c39e7',
            '1527864550417-7fd91fc51a46',
            '1542751371-adc38448a05e',
        ],
        'mechanical-keyboard' => [
            '1595044426077-d36d9236d54a',
            '1618384887929-16ec33fab9ef',
            '1587829741301-dc798b83add3',
        ],
        'apple-watch' => [
            '1546868871-7041f2a55e12',
            '1579586337278-3befd40fd17a',
            '1434493789847-2f02dc6ca35d',
        ],
        'smartwatch' => [
            '1579586337278-3befd40fd17a',
            '1434493789847-2f02dc6ca35d',
            '1546868871-7041f2a55e12',
        ],
        'default' => [
            '1523275335684-37898b6baf30',
            '1519389950473-47ba0277781c',
            '1560343090-f0409e92791a',
        ],
    ];

    /**
     * Chọn ảnh Unsplash theo keyword + index để mỗi SP có ảnh khác nhau.
     */
    private function pickImage(string $keyword, int $productIdx, int $imgIdx): string
    {
        $ids = self::IMAGE_MAP[$keyword] ?? self::IMAGE_MAP['default'];
        $id = $ids[($productIdx + $imgIdx - 1) % count($ids)];

        return "https://images.unsplash.com/photo-{$id}?w=800&h=800&fit=crop&q=80";
    }

    /**
     * Sinh mô tả HTML theo nhóm sản phẩm để tránh các SP giống nhau.
     */
    private function makeDescription(string $categorySlug, string $shortDesc): string
    {
        $specs = match ($categorySlug) {
            'smartphone', 'dien-thoai-gaming', 'dien-thoai-pho-thong' => [
                'Màn hình AMOLED/OLED sắc nét, tần số quét cao, hiển thị tốt ngoài trời.',
                'Chip xử lý mạnh mẽ, đáp ứng tốt mọi tác vụ và game nặng.',
                'Camera chất lượng cao với nhiều chế độ chụp đêm, chân dung, quay video 4K.',
                'Pin dung lượng lớn, hỗ trợ sạc nhanh, dùng thoải mái 1–2 ngày.',
                'Hệ điều hành mượt mà, được cập nhật phần mềm dài hạn.',
            ],
            'laptop-gaming', 'laptop-van-phong', 'macbook' => [
                'Màn hình độ phân giải cao, màu sắc chính xác, phù hợp làm việc và giải trí.',
                'Bàn phím êm, có đèn nền LED tiện dùng ban đêm.',
                'Hệ thống tản nhiệt hiệu quả, duy trì hiệu năng ổn định trong thời gian dài.',
                'Cổng kết nối đa dạng: USB-C, USB-A, HDMI, jack tai nghe 3.5mm.',
                'Pin dung lượng lớn, sạc nhanh, dùng được cả ngày làm việc.',
            ],
            'ipad', 'android-tablet' => [
                'Màn hình lớn, màu sắc sống động, phù hợp học tập, ghi chú và giải trí.',
                'Hiệu năng mạnh mẽ, xử lý đa nhiệm và ứng dụng đồ hoạ tốt.',
                'Hỗ trợ bút cảm ứng và bàn phím rời (bán riêng) để tăng năng suất.',
                'Pin dùng liên tục 8–10 giờ, sạc qua USB-C tiện lợi.',
            ],
            'tai-nghe', 'tws' => [
                'Chất âm chi tiết: bass sâu, mid rõ, treble trong.',
                'Chống ồn chủ động (ANC) giúp tập trung làm việc và thư giãn.',
                'Pin lâu, sạc nhanh qua cổng USB-C.',
                'Kết nối Bluetooth ổn định, độ trễ thấp khi xem phim, chơi game.',
            ],
            'loa-bluetooth' => [
                'Âm thanh mạnh mẽ, bass sống động, phù hợp tiệc nhỏ và dã ngoại.',
                'Chống nước IPX7, dùng được ngoài trời và cạnh hồ bơi.',
                'Pin lâu, kết nối Bluetooth 5.x ổn định trong phạm vi 10m.',
            ],
            'smartwatch' => [
                'Theo dõi sức khoẻ toàn diện: nhịp tim, SpO2, giấc ngủ, stress.',
                'Màn hình AMOLED sáng, hiển thị tốt ngoài trời.',
                'Pin dùng 1–2 ngày, sạc nhanh qua đế từ tính.',
                'Chống nước, dùng được khi rửa tay và bơi lội.',
            ],
            'sac-cap' => [
                'Chuẩn sạc nhanh PD/QC, tương thích nhiều thiết bị.',
                'Chất liệu bền, chip bảo vệ chống quá dòng, quá nhiệt.',
                'Nhỏ gọn, tiện mang theo khi đi công tác, du lịch.',
            ],
            'op-lung-bao-da' => [
                'Chất liệu cao cấp, bảo vệ máy khỏi va đập và trầy xước.',
                'Thiết kế ôm sát, không cấn máy, dễ tháo lắp.',
                'Hỗ trợ sạc không dây (nếu thiết bị hỗ trợ).',
            ],
            'chuot-ban-phim' => [
                'Thiết kế ergonomic, dùng lâu không mỏi tay.',
                'Kết nối không dây ổn định hoặc có dây tuỳ chọn.',
                'Độ chính xác cao, phản hồi nhanh, phù hợp làm việc và gaming.',
            ],
            default => [
                'Sản phẩm chính hãng, bảo hành đầy đủ.',
                'Chất lượng cao, thiết kế đẹp, phù hợp nhiều đối tượng.',
            ],
        };

        $html = '<p>'.e($shortDesc).'</p>';
        $html .= '<h3>Đặc điểm nổi bật</h3>';
        $html .= '<ul>';
        foreach ($specs as $s) {
            $html .= '<li>'.e($s).'</li>';
        }
        $html .= '</ul>';
        $html .= '<h3>Bảo hành &amp; Đổi trả</h3>';
        $html .= '<p>Sản phẩm được bảo hành <strong>12 tháng</strong> tại trung tâm uỷ quyền toàn quốc. ';
        $html .= 'Đổi trả trong <strong>7 ngày</strong> nếu có lỗi từ nhà sản xuất.</p>';

        return $html;
    }
}
