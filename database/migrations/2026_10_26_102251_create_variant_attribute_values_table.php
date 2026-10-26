<?php

// Tạo bảng gán giá trị thuộc tính cho từng biến thể.

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
        Schema::create('variant_attribute_values', function (Blueprint $t) {
            $t->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $t->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $t->primary(['variant_id', 'attribute_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_attribute_values');
    }
};
