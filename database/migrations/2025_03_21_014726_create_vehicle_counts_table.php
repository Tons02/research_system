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
        Schema::create('vehicle_counts', function (Blueprint $table) {
            $table->increments('id');
            $table->date("date");
            $table->time("time_range");
            $table->enum("time_period", ["AM", "PM"]);
            $table->integer('total_left_private_car');
            $table->integer('total_left_truck');
            $table->integer('total_left_jeepney');
            $table->integer('total_left_bus');
            $table->integer('total_left_tricycle');
            $table->integer('total_left_bicycle');
            $table->integer('total_left_e_bike');
            $table->integer('total_left');
            $table->integer('total_right_private_car');
            $table->integer('total_right_truck');
            $table->integer('total_right_jeepney');
            $table->integer('total_right_bus');
            $table->integer('total_right_tricycle');
            $table->integer('total_right_bicycle');
            $table->integer('total_right_e_bike');
            $table->integer('total_right');
            $table->integer('grand_total');
            $table->unsignedInteger("surveyor_id")->index();
            $table->unsignedInteger("target_location_id")->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("surveyor_id")
                ->references("id")
                ->on("users");

            $table->foreign("target_location_id")
                ->references("id")
                ->on("target_locations");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_counts');
    }
};
