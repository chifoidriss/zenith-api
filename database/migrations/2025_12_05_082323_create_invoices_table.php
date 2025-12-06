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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('devise_id')->nullable();
            $table->foreign('devise_id')->references('id')->on('devises')->nullOnDelete();

            $table->unsignedBigInteger('partner_id')->nullable();
            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();

            $table->enum('type', ['INVOICE', 'REFUND'])->index();
            $table->string('source', 50)->index();
            $table->string('reference', 50)->index();
            $table->date('billing_date')->nullable();
            $table->date('due_date')->nullable();
            $table->double('subtotal')->nullable();
            $table->double('total')->nullable();
            $table->double('discount')->nullable();
            $table->boolean('status')->default(0);
            $table->string('label')->nullable();
            $table->longText('taxes')->nullable();
            $table->string('document', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
