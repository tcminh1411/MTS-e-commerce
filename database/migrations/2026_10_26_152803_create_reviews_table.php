<?php

// Tạo bảng đánh giá sản phẩm của người dùng.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('rating');        // 1..5
            $table->text('comment')->nullable();
            $table->boolean('is_visible')->default(true);

            $table->timestamps();

            // 1 user chỉ review 1 lần cho 1 sản phẩm trong 1 đơn
            $table->unique(['user_id', 'product_id', 'order_id'], 'uk_user_product_order');

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
