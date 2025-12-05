<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('main_id')->index()->nullable();
            $table->unsignedBigInteger('sub_id')->index()->nullable();
            $table->enum('role', ['main', 'sub', 'child'])->index();
            $table->string('name')->index();
            $table->timestamps();

            $table->foreign('main_id')->references('id')->on('account_types')->onDelete('cascade');
            $table->foreign('sub_id')->references('id')->on('account_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_types');
    }
};
