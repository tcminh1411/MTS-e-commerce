<?php

// Tạo bảng người dùng.

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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->unique()->nullable();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('password');
            $table->string('full_name', 150)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->string('gender', 16)->nullable();
            $table->date('birthday')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
