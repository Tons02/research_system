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
        Schema::create('business_units', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('sync_id');
            $table->string('business_unit_code');
            $table->string('business_unit_name');
            $table->unsignedInteger('company_id')->index();

            $table->foreign("company_id")
            ->references("id")
            ->on("companies")
            ->onDelete('cascade');


            $table->boolean('is_active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_units');
    }
};
