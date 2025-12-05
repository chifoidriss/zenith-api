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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->index()->nullable();
            $table->enum('role', ['MAIN', 'CHILD'])->index();
            $table->string('name', 100)->index();
            $table->string('code', 10)->nullable();
            $table->unsignedInteger('unity')->default(1);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->foreign('parent_id')->references('id')->on('units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
