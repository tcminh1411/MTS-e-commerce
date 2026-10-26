<?php

// Tạo bảng điều kiện áp dụng mã giảm giá.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();

            // 'category' | 'product' | 'brand'
            // Nếu coupon KHÔNG có condition nào → áp dụng cho mọi sản phẩm
            $table->string('condition_type', 20);
            $table->unsignedBigInteger('condition_id');

            $table->timestamps();

            $table->index(['coupon_id', 'condition_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_conditions');
    }
};
