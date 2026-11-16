<?php

// Tạo bảng quyền của Spatie Permission.

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
        $tableName = config('permission.table_names.permissions');

        throw_if(empty($tableName), 'Error: config/permission.php not loaded.');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('permission.table_names.permissions');

        throw_if(empty($tableName), 'Error: config/permission.php not loaded.');

        Schema::dropIfExists($tableName);
    }
};
