<?php

// Tạo bảng khu vực giao hàng.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // "Miền Bắc", "Miền Trung", "Miền Nam"
            $table->string('code', 50)->unique();         // "MB", "MT", "MN"
            $table->json('provinces')->nullable();        // ["Hà Nội", "Hải Phòng", ...]
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
