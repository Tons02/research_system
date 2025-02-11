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
        Schema::create('sub_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sync_id')->unique();
            $table->string('sub_unit_code');
            $table->string('sub_unit_name');
            $table->bigInteger('unit_id')->index();
            $table->foreign("unit_id")
            ->references("sync_id")
            ->on("units")
            ->onDelete('cascade');

            $table->timestamps();
            $table->softdeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_units');
    }
};
