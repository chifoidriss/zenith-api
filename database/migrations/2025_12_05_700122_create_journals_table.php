<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_account_id')->nullable();
            $table->foreign('product_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->foreign('expense_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('suspense_account_id')->nullable();
            $table->foreign('suspense_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('cash_account_id')->nullable();
            $table->foreign('cash_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('profit_account_id')->nullable();
            $table->foreign('profit_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('loss_account_id')->nullable();
            $table->foreign('loss_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->foreign('bank_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->enum('type', ['SALE', 'PURCHASE', 'BANK', 'CASH', 'OTHER'])->index();
            $table->string('name');
            $table->string('short_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('journals');
    }
};
