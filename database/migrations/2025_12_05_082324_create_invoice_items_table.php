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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('article_id')->constrained();
            $table->foreignId('unit_id')->constrained();
            $table->float('qty')->unsigned()->nullable();
            $table->double('price')->nullable();
            $table->string('discount')->nullable();
            $table->string('label')->nullable();

            $table->double('subtotal')->nullable();
            $table->enum('calcul', ['D', 'H'])->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
