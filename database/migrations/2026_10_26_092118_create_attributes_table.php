<?php

// Tạo bảng thuộc tính dùng chung cho sản phẩm.

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
        Schema::create('attributes', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('slug', 100)->unique();
            $t->string('type', 16)->default('select');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
