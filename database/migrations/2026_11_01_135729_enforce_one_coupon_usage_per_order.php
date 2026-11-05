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
        $hasUnlinkedUsages = DB::table('coupon_usages')
            ->whereNull('order_id')
            ->exists();
        $hasDuplicateUsages = DB::table('coupon_usages')
            ->select('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasUnlinkedUsages || $hasDuplicateUsages) {
            throw new RuntimeException(
                'Cannot enforce one coupon usage per order while coupon_usages contains unlinked or duplicate orders.'
            );
        }

        Schema::table('coupon_usages', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
        });

        Schema::table('coupon_usages', function (Blueprint $table): void {
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
        });

        Schema::table('coupon_usages', function (Blueprint $table): void {
            $table->unique('order_id');
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->dropUnique(['order_id']);
        });

        Schema::table('coupon_usages', function (Blueprint $table): void {
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();
        });
    }
};
