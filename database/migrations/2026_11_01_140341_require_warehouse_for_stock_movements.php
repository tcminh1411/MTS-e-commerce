<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('stock_movements')->whereNull('warehouse_id')->exists()) {
            throw new RuntimeException(
                'Cannot require a warehouse while stock_movements contains rows without warehouse_id.'
            );
        }

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropForeign(['warehouse_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->unsignedBigInteger('warehouse_id')->nullable(false)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropForeign(['warehouse_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->unsignedBigInteger('warehouse_id')->nullable()->change();
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->nullOnDelete();
        });
    }
};
