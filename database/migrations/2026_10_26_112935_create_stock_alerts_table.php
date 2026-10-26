<?php

// Tạo bảng đăng ký nhận thông báo khi sản phẩm có hàng.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            // Lưu ý: MySQL coi NULL là distinct trong unique index,
            // nên unique này chỉ chặn được khi variant_id NOT NULL.
            // Controller sẽ check trùng thủ công cho case variant_id = NULL.
            $table->unique(['product_id', 'variant_id', 'email'], 'stock_alerts_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
