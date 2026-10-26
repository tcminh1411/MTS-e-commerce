<?php

// Tạo bảng yêu cầu trả hàng của đơn hàng.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();

            // 1 đơn = 1 return request (unique)
            $table->foreignId('order_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            // Guest có thể tạo (nhưng chỉ khi có session) → nullable
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->text('reason'); // lý do user

            // pending | approved | rejected | refunded
            $table->string('status', 30)->default('pending');

            $table->text('admin_note')->nullable(); // lý do admin duyệt/từ chối
            $table->unsignedBigInteger('refund_amount')->nullable(); // số tiền hoàn
            $table->string('refund_method', 50)->nullable(); // cash|bank_transfer|vnpay

            // Admin xử lý cuối (approve/reject/refund)
            $table->foreignId('processed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();  // khi approve/reject
            $table->timestamp('refunded_at')->nullable();   // khi bấm "Đã hoàn tiền"

            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
