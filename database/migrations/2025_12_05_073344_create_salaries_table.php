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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained();
            $table->foreignId('contract_id')->constrained();
            $table->foreignId('invoice_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedFloat('salary_total')->default(0);
            $table->unsignedFloat('bonus_total')->default(0);
            $table->unsignedFloat('indemnity_total')->default(0);
            $table->unsignedFloat('leave_total')->default(0);
            $table->unsignedFloat('absence_total')->default(0);
            $table->unsignedFloat('tax_irpp_total')->default(0);
            $table->unsignedFloat('tax_cac_total')->default(0);
            $table->unsignedFloat('tax_cfc_total')->default(0);
            $table->unsignedFloat('tax_crtv_total')->default(0);
            $table->unsignedFloat('tax_municipal_total')->default(0);
            $table->unsignedFloat('pvid_total')->default(0);
            $table->unsignedFloat('syndical_total')->default(0);
            $table->unsignedFloat('salary_advance')->default(0);
            $table->unsignedFloat('salary_loan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
