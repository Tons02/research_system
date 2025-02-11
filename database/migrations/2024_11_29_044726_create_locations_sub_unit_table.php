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
        Schema::create('locations_sub_unit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_unit_id');
            $table->unsignedBigInteger('location_id');

            $table->foreign('sub_unit_id')->references('sync_id')->on('sub_units')->onDelete('cascade');
            $table->foreign('location_id')->references('sync_id')->on('locations')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations_sub_unit');
    }
};
