<?php

namespace Tests\Feature\Shop;

use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class ChatbotControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_returns_service_reply(): void
    {
        $this->mock(ChatService::class)
            ->shouldReceive('reply')
            ->once()
            ->with('Cần tư vấn sản phẩm', [])
            ->andReturn('Mình có thể giúp gì?');

        $this->postJson(route('chatbot.reply'), ['message' => 'Cần tư vấn sản phẩm'])
            ->assertOk()
            ->assertJsonPath('reply', 'Mình có thể giúp gì?');
    }

    public function test_chatbot_logs_failure_and_returns_generic_error(): void
    {
        $exception = new RuntimeException('Secret upstream response');

        $this->mock(ChatService::class)
            ->shouldReceive('reply')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('Chatbot reply failed', \Mockery::on(
                fn (array $context): bool => ($context['exception'] ?? null) === $exception
            ));

        $this->postJson(route('chatbot.reply'), ['message' => 'Xin chào'])
            ->assertInternalServerError()
            ->assertJson([
                'ok' => false,
                'message' => 'Có lỗi xảy ra, vui lòng thử lại.',
            ])
            ->assertJsonMissing(['message' => 'Secret upstream response']);
    }

    public function test_chatbot_limits_requests_to_ten_per_minute(): void
    {
        $this->mock(ChatService::class)
            ->shouldReceive('reply')
            ->times(10)
            ->andReturn('OK');

        for ($request = 0; $request < 10; $request++) {
            $this->postJson(route('chatbot.reply'), ['message' => 'Xin chào'])
                ->assertOk();
        }

        $this->postJson(route('chatbot.reply'), ['message' => 'Xin chào'])
            ->assertTooManyRequests();
    }
}
