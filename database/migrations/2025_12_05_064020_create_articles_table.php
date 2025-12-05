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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();

            $table->unsignedBigInteger('unit_id')->nullable();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();

            $table->unsignedBigInteger('sale_unit_id')->nullable();
            $table->foreign('sale_unit_id')->references('id')->on('units')->nullOnDelete();

            $table->unsignedBigInteger('purchase_unit_id')->nullable();
            $table->foreign('purchase_unit_id')->references('id')->on('units')->nullOnDelete();

            $table->unsignedBigInteger('product_account_id')->nullable();
            $table->foreign('product_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->foreign('expense_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('stock_account_id')->nullable();
            $table->foreign('stock_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->unsignedBigInteger('commodity_account_id')->nullable();
            $table->foreign('commodity_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->string('name');
            $table->enum('type', ['STOCKABLE', 'CONSUMABLE', 'SERVICE', 'ROOM', 'MENU'])->index()->nullable();
            $table->string('reference', 100)->index()->nullable();
            $table->string('barcode', 100)->index()->nullable();
            $table->string('tags', 100)->index()->nullable();
            $table->double('price')->default(0);
            $table->double('cost')->default(0);
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('volume', 10, 2)->nullable();
            $table->longText('image_path')->nullable();
            $table->boolean('can_sale')->default(false);
            $table->boolean('can_purchase')->default(false);
            $table->boolean('can_rented')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
