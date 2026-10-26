<?php

// Tạo bảng danh sách sản phẩm yêu thích của người dùng.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // 1 user chỉ thích 1 product 1 lần
            $table->unique(['user_id', 'product_id'], 'wishlists_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
