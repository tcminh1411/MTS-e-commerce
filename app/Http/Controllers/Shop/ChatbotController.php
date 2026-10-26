<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Xử lý các yêu cầu liên quan đến trợ lý tư vấn. */
class ChatbotController extends Controller
{
    public function reply(Request $request, ChatService $chat): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['nullable', 'string', 'in:user,assistant'],
            'history.*.content' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $reply = $chat->reply($data['message'], $data['history'] ?? []);

            return response()->json(['ok' => true, 'reply' => $reply]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
