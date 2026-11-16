<?php

// Tạo bảng đăng ký nhận thông báo khi sản phẩm có hàng.

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
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->string('email');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'variant_id', 'email'], 'stock_alerts_uniq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
