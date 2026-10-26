<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Xử lý nghiệp vụ AI tư vấn dùng chung. */
class AIService
{
    public function generateProductDescription(string $productName, string $categoryName): string
    {
        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            throw new RuntimeException('Chưa cấu hình GROQ_API_KEY.');
        }

        $prompt = "Viết đoạn mô tả sản phẩm chuẩn SEO bằng tiếng Việt cho: {$productName} (Danh mục: {$categoryName}). Yêu cầu: 1 đoạn <p> mở đầu, 1 danh sách <ul><li> đặc điểm. Chỉ trả về HTML, không dùng markdown.";

        $response = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'openai/gpt-oss-120b',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 500,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Lỗi AI: '.$response->status());
        }

        $text = $response->json()['choices'][0]['message']['content'] ?? '';

        return trim(str_replace(['```html', '```'], '', $text));
    }
}
