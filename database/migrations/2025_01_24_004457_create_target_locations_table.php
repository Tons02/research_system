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
            $table->string('province_psgc_id');
            $table->string('province');
            $table->string('city_municipality_psgc_id');
            $table->string('city_municipality');
            $table->string('sub_municipality_psgc_id');
            $table->string('sub_municipality');
            $table->string('barangay_psgc_id');
            $table->string('barangay');
            $table->unsignedInteger("form_id")->index();

            $table->foreign("form_id")
            ->references("id")
            ->on("forms");

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
