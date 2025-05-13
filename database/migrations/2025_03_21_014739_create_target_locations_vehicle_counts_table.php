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
        Schema::create('target_locations_vehicle_counts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger("vehicle_count_id")->index();
            $table->unsignedInteger("target_location_id")->index();

            $table->foreign('vehicle_count_id')
                  ->references('id')
                  ->on('vehicle_counts')
                  ->onDelete('cascade');

            $table->foreign('target_location_id')
                  ->references('id')
                  ->on('target_locations')
                  ->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_locations_vehicle_counts');
    }
};
