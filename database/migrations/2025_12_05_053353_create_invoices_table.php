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
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();

            $table->unsignedBigInteger('devise_id')->nullable();
            $table->foreign('devise_id')->references('id')->on('devises')->nullOnDelete();

            $table->string('reference')->unique();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->double('total')->default(0);
            $table->boolean('status')->default(false); // Paid or not
            $table->string('document')->nullable();
            $table->text('notes')->nullable();
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
