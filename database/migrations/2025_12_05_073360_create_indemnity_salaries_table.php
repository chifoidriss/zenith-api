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
        Schema::create('indemnity_salary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indemnity_id')->constrained();
            $table->foreignId('salary_id')->constrained();
            $table->decimal('value', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indemnity_salary');
    }
};
