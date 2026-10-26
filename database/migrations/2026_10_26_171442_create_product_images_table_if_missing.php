<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration này tạo bảng product_images NẾU CHƯA CÓ.
 *
 * Lý do: migration gốc (2026_09_28_182225_create_product_images_table)
 * đã bị xoá khỏi project nhưng bảng đã tồn tại trong DB từ trước.
 * File này đảm bảo môi trường mới (clone, VPS, migrate:fresh) vẫn có bảng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_images')) {
            return;
        }

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('url', 500);
            $table->string('alt', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        // Không drop — tránh xoá nhầm data nếu user lỡ rollback.
    }
};
