<?php

// Tạo bảng nội dung các trang tĩnh.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255)->unique();
            $table->string('title', 255);
            $table->longText('body');
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedSmallInteger('footer_sort')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index(['show_in_footer', 'is_active', 'footer_sort'], 'idx_pages_footer_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
