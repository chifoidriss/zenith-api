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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_account_id')->nullable();
            $table->foreign('cash_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('suspense_account_id')->nullable();
            $table->foreign('suspense_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('profit_account_id')->nullable();
            $table->foreign('profit_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('loss_account_id')->nullable();
            $table->foreign('loss_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
