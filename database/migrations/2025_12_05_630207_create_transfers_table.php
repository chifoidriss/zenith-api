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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();

            $table->unsignedBigInteger('origin_warehouse_id')->nullable();
            $table->foreign('origin_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();

            $table->unsignedBigInteger('destination_warehouse_id')->nullable();
            $table->foreign('destination_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();

            $table->enum('operation', ['TRANSFER', 'APPOINTMENT', 'DELIVERY', 'RETURN'])->index();
            $table->string('reference', 50)->index();
            $table->string('document')->nullable();
            $table->date('billing_date')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
