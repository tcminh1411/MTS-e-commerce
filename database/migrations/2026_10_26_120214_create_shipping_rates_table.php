<?php

// Tạo bảng mức phí giao hàng theo khu vực và điều kiện.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')
                ->constrained('shipping_zones')
                ->cascadeOnDelete();
            $table->string('name');                              // "Tiêu chuẩn", "Nhanh"
            $table->unsignedInteger('min_weight_gram')->nullable();
            $table->unsignedInteger('max_weight_gram')->nullable();
            $table->unsignedBigInteger('min_order_value')->nullable();
            $table->unsignedBigInteger('max_order_value')->nullable();
            $table->unsignedBigInteger('fee');                   // phí ship (VND)
            $table->unsignedTinyInteger('estimated_days_min')->nullable();
            $table->unsignedTinyInteger('estimated_days_max')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['zone_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
