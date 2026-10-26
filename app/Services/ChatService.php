<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Xử lý nghiệp vụ tư vấn trực tuyến dùng chung. */
class ChatService
{
    public function reply(string $message, array $history = []): string
    {
        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            throw new RuntimeException('Chưa cấu hình GROQ_API_KEY trong .env');
        }

        $products = Product::where('status', 'active')->orderByDesc('sold_count')->get();
        $total = $products->count();

        $saleProducts = $products->filter(fn ($p) => $p->sale_price && $p->sale_price < $p->price);
        $saleCount = $saleProducts->count();

        $saleList = $saleProducts->take(10)->map(fn ($p) => '- '.$p->name.' | Gốc: '.number_format($p->price, 0, ',', '.')
            .'đ → '.number_format($p->sale_price, 0, ',', '.')
            .'đ (-'.round((1 - $p->sale_price / $p->price) * 100).'%)'
        )->implode("\n") ?: '(Không có sản phẩm giảm giá)';

        $topList = $products->take(5)->map(fn ($p) => '- '.$p->name.' | Đã bán: '.$p->sold_count
            .' | Giá: '.number_format($p->sale_price ?? $p->price, 0, ',', '.').'đ'
        )->implode("\n");

        $catalog = $products->map(fn ($p) => '- '.$p->name.' | Giá: '.number_format($p->sale_price ?? $p->price, 0, ',', '.')
            .'đ | Tồn: '.$p->stock
        )->implode("\n");

        $system = "Bạn là nhân viên tư vấn bán hàng của MTS (shop công nghệ).

=== SỐ LIỆU CHÍNH XÁC (KHÔNG ĐƯỢC TỰ ĐẾM LẠI) ===
TỔNG SẢN PHẨM ĐANG BÁN: {$total}
SỐ SẢN PHẨM ĐANG GIẢM GIÁ: {$saleCount}

=== TOP 5 BÁN CHẠY ===
{$topList}

=== SẢN PHẨM GIẢM GIÁ (tối đa 10) ===
{$saleList}

=== DANH MỤC ĐẦY ĐỦ ===
{$catalog}

=== QUY TẮC BẮT BUỘC ===
1. Chào hỏi → chào lại thân thiện, hỏi cần tư vấn gì.
2. Hỏi 'bao nhiêu sản phẩm' → trả lời đúng {$total}, KHÔNG đếm lại.
3. Hỏi 'giảm giá / sale / khuyến mãi' → trả lời đúng {$saleCount} + liệt kê từ danh sách giảm giá.
4. Hỏi 'bán chạy / hot' → dựa TOP 5.
5. Hỏi sản phẩm cụ thể → tìm trong catalog, báo giá + tồn.
6. Hỏi ngân sách ('tai nghe dưới 5 triệu') → lọc catalog theo giá.
7. Hỏi đơn hàng cá nhân → hướng dẫn vào menu 'Đơn hàng', KHÔNG có quyền truy cập.
8. Xưng 'em', gọi khách 'anh/chị'. Trả lời ngắn gọn, dùng bullet.
9. KHÔNG bịa sản phẩm/giá ngoài danh sách trên.
10. Ngoài lề (chính trị, tôn giáo) → từ chối khéo, quay lại chủ đề mua sắm.";

        $messages = [['role' => 'system', 'content' => $system]];
        foreach (array_slice($history, -10) as $h) {
            if (! isset($h['role'], $h['content'])) {
                continue;
            }
            $role = in_array($h['role'], ['user', 'assistant'], true) ? $h['role'] : 'user';
            $messages[] = ['role' => $role, 'content' => (string) $h['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $response = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'openai/gpt-oss-120b',
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 600,
        ]);

        if ($response->failed()) {
            Log::error('Groq Chat Error', ['body' => $response->body()]);
            throw new RuntimeException('Lỗi kết nối AI');
        }

        return trim($response->json()['choices'][0]['message']['content'] ?? 'Xin lỗi, em đang bận.');
    }
}
