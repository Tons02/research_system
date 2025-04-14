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
        Schema::create('target_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('region_psgc_id');
            $table->string('region');
            $table->string('province_psgc_id')->nullable();
            $table->string('province')->nullable();
            $table->string('city_municipality_psgc_id');
            $table->string('city_municipality');
            $table->string('sub_municipality_psgc_id')->nullable();
            $table->string('sub_municipality')->nullable();
            $table->string('barangay_psgc_id');
            $table->string('barangay');
            $table->string('street')->nullable();
            $table->json('bound_box')->nullable();

            $table->integer('response_limit');
            $table->unsignedInteger("form_id")->index()->nullable();
            $table->unsignedInteger("form_history_id")->index()->nullable();
            $table->unsignedInteger("vehicle_counted_by_user_id")->index();
            $table->unsignedInteger("foot_counted_by_user_id")->index();
            $table->boolean('is_done')->default(false);
            $table->boolean('is_final')->default(false);

            $table->foreign("form_id")
                ->references("id")
                ->on("forms")
                ->onDelete("cascade");

            $table->foreign("form_history_id")
                ->references("id")
                ->on("form_histories")
                ->onDelete("cascade");

            // relationship on users
            $table->foreign("vehicle_counted_by_user_id")
                ->references("id")
                ->on("users");


            $table->foreign("foot_counted_by_user_id")
                ->references("id")
                ->on("users");


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_locations');
    }
};
