<?php

// Tạo bảng lưu thông tin và trạng thái sản phẩm.

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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 64)->nullable()->unique();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            $table->string('product_type', 16)->default('simple'); // simple|variable|bundle
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();

            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->string('currency', 8)->default('VND');

            $table->integer('weight_gram')->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();

            $table->string('status', 16)->default('draft'); // draft|active|inactive
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('sold_count')->default(0);

            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('price');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
