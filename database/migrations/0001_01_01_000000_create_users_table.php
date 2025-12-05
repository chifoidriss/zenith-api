<?php

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
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('title', 50)->nullable();
            $table->string('email', 100)->unique();
            $table->string('phone', 50)->unique()->nullable();
            $table->string('username', 50)->unique()->nullable();
            $table->enum('genre', ['M','F'])->nullable();
            $table->date('birthday')->nullable();
            $table->string('language', 20)->nullable();
            $table->string('timezone', 20)->nullable();
            $table->string('avatar_url', 255)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->dateTime('last_logged_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->string('password', 255);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
