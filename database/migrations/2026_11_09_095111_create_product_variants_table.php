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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('name', 200);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->integer('weight_gram')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('stock')->default(0);
            $table->string('status', 16)->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
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
