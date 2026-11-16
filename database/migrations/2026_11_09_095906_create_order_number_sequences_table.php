<?php

// Tạo bảng cấp số thứ tự đơn hàng theo ngày.

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
        Schema::create('order_number_sequences', function (Blueprint $table) {
            $table->string('sequence_date', 8)->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_number_sequences');
    }
};
