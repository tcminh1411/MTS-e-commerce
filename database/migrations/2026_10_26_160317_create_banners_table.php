<?php

// Tạo bảng banner hiển thị trên cửa hàng.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();

            $table->string('title', 255)->nullable();
            $table->string('image', 500);           // URL external hoặc path storage
            $table->string('link', 500)->nullable(); // URL khi click

            // Vị trí hiển thị: home_top | home_middle | home_bottom | sidebar
            $table->string('position', 30)->default('home_top');

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['position', 'is_active', 'sort_order'], 'idx_banner_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
