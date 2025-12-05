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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chart_account_id')->nullable();
            $table->string('name');
            $table->enum('calcul', ['FIXED', 'PERCENT']);
            $table->enum('type', ['IN', 'OUT']);
            $table->decimal('value', 10, 2);
            $table->string('label')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('chart_account_id')->references('id')->on('chart_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
