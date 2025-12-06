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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->foreign('nationality_id')->references('id')->on('countries')->nullOnDelete();

            $table->unsignedBigInteger('country_id')->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();

            $table->unsignedBigInteger('default_account_id')->nullable();
            $table->foreign('default_account_id')->references('id')->on('chart_accounts')->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('barcode', 100)->index()->nullable();
            $table->string('reference', 100)->index()->nullable();
            $table->string('identity_type', 50)->nullable();
            $table->string('identity_number', 100)->index()->nullable();
            $table->string('identity_delivery_place', 100)->nullable();
            $table->date('identity_delivery_date', 100)->nullable();
            $table->date('identity_expiry_date', 100)->nullable();
            $table->enum('type', ['CLIENT', 'SUPPLIER', 'SALARY', 'OTHER'])->index();
            $table->enum('partner_type', ['PARTICULAR', 'SOCIETY'])->default('PARTICULAR');
            $table->enum('genre', ['M', 'F'])->nullable();
            $table->string('title')->nullable();
            $table->string('society_name')->nullable();
            $table->date('birthday')->nullable();
            $table->string('birthplace')->nullable();
            $table->string('post')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
