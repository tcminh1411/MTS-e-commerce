<?php

// Tạo bảng bài viết và thông tin xuất bản.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_category_id')
                ->nullable()
                ->constrained('post_categories')
                ->nullOnDelete();
            $table->string('slug', 255)->unique();
            $table->string('title', 255);
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');
            $table->string('thumbnail', 500)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
