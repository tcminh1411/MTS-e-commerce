<?php

// Tạo bảng mã giảm giá và thông tin áp dụng.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // 'fixed' = giảm số tiền cố định, 'percent' = giảm theo %
            $table->string('type', 20)->default('fixed');

            // Nếu type='fixed' → value là số tiền (VND)
            // Nếu type='percent' → value là % (1–100)
            $table->unsignedBigInteger('value');

            // Chỉ dùng khi type='percent': giảm tối đa bao nhiêu tiền
            $table->unsignedBigInteger('max_discount_amount')->nullable();

            // Điều kiện đơn tối thiểu
            $table->unsignedBigInteger('min_order_amount')->default(0);

            // Giới hạn số lần dùng
            $table->unsignedInteger('usage_limit')->nullable();           // tổng
            $table->unsignedInteger('usage_limit_per_user')->nullable();  // mỗi user
            $table->unsignedInteger('used_count')->default(0);

            // Thời gian hiệu lực
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
