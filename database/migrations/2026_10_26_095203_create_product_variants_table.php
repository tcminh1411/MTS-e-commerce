<?php

// Tạo bảng biến thể sản phẩm và thông tin tồn kho tổng.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('sku', 64)->unique();
            $t->string('name', 200);
            $t->decimal('price', 15, 2)->default(0);
            $t->decimal('sale_price', 15, 2)->nullable();
            $t->decimal('cost_price', 15, 2)->nullable();
            $t->integer('weight_gram')->nullable();
            $t->string('image_url', 500)->nullable();
            $t->boolean('is_default')->default(false);
            $t->string('status', 16)->default('active');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
            $t->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
