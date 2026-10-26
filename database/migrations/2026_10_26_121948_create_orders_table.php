<?php

// Tạo bảng đơn hàng và thông tin thanh toán, giao nhận.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();  // ORD-20260930-0001

            // Chủ đơn: user login hoặc guest
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('guest_email')->nullable();

            // Trạng thái
            $table->string('status', 30)->default('pending');          // pending|confirmed|processing|shipped|delivered|cancelled|refunded
            $table->string('payment_status', 30)->default('unpaid');   // unpaid|paid|failed|refunded
            $table->string('payment_method', 20)->default('cod');      // cod|vnpay

            // Tiền (VND, đơn vị đồng, dùng unsignedBigInteger)
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('shipping_fee')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('coupon_code', 50)->nullable();   // snapshot code, không FK

            // Shipping snapshot (không FK bắt buộc vì rate có thể bị xoá sau này)
            $table->foreignId('shipping_zone_id')->nullable()
                ->constrained('shipping_zones')->nullOnDelete();
            $table->foreignId('shipping_rate_id')->nullable()
                ->constrained('shipping_rates')->nullOnDelete();
            $table->string('shipping_rate_name');   // snapshot tên rate

            // Người nhận (snapshot tại thời điểm đặt)
            $table->string('recipient_name');
            $table->string('recipient_phone', 20);
            $table->string('recipient_email')->nullable();
            $table->text('shipping_address');

            $table->text('note')->nullable();
            $table->timestamp('placed_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('payment_status');
            $table->index('user_id');
            $table->index('placed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
