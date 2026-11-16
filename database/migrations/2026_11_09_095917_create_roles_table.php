<?php

// Tạo bảng vai trò của Spatie Permission.

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
        $teams = config('permission.teams');
        $tableName = config('permission.table_names.roles');
        $columnNames = config('permission.column_names');

        throw_if(empty($tableName), 'Error: config/permission.php not loaded.');
        throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), 'Error: team_foreign_key on config/permission.php not loaded.');

        Schema::create($tableName, function (Blueprint $table) use ($teams, $columnNames) {
            $table->id();
            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('permission.table_names.roles');

        throw_if(empty($tableName), 'Error: config/permission.php not loaded.');

        Schema::dropIfExists($tableName);
    }
};
