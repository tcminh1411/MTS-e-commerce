<?php

// Tạo bảng chi tiết sản phẩm thuộc từng đơn hàng.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')->cascadeOnDelete();

            // Tham chiếu sản phẩm (nullable vì product có thể bị xoá sau)
            $table->foreignId('product_id')->nullable()
                ->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();

            // Snapshot — KHÔNG phụ thuộc product sau khi đặt
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku', 100)->nullable();

            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');

            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
