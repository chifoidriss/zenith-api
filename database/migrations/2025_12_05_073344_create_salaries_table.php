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
            $table->decimal('salary_total', 10, 2)->default(0);
            $table->decimal('bonus_total', 10, 2)->default(0);
            $table->decimal('indemnity_total', 10, 2)->default(0);
            $table->decimal('leave_total', 10, 2)->default(0);
            $table->decimal('absence_total', 10, 2)->default(0);
            $table->decimal('tax_irpp_total', 10, 2)->default(0);
            $table->decimal('tax_cac_total', 10, 2)->default(0);
            $table->decimal('tax_cfc_total', 10, 2)->default(0);
            $table->decimal('tax_crtv_total', 10, 2)->default(0);
            $table->decimal('tax_municipal_total', 10, 2)->default(0);
            $table->decimal('pvid_total', 10, 2)->default(0);
            $table->decimal('syndical_total', 10, 2)->default(0);
            $table->decimal('salary_advance', 10, 2)->default(0);
            $table->decimal('salary_loan', 10, 2)->default(0);
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
