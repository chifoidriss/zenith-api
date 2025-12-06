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
            $table->float('salary_total')->unsigned()->default(0);
            $table->float('bonus_total')->unsigned()->default(0);
            $table->float('indemnity_total')->unsigned()->default(0);
            $table->float('leave_total')->unsigned()->default(0);
            $table->float('absence_total')->unsigned()->default(0);
            $table->float('tax_irpp_total')->unsigned()->default(0);
            $table->float('tax_cac_total')->unsigned()->default(0);
            $table->float('tax_cfc_total')->unsigned()->default(0);
            $table->float('tax_crtv_total')->unsigned()->default(0);
            $table->float('tax_municipal_total')->unsigned()->default(0);
            $table->float('pvid_total')->unsigned()->default(0);
            $table->float('syndical_total')->unsigned()->default(0);
            $table->float('salary_advance')->unsigned()->default(0);
            $table->float('salary_loan')->unsigned()->default(0);
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
