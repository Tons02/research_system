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
            $table->bigInteger('sync_id')->unique();
            $table->string('business_unit_code');
            $table->string('business_unit_name');
            $table->bigInteger('company_id')->index();

            $table->foreign("company_id")
            ->references("sync_id")
            ->on("companies")
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
        Schema::dropIfExists('business_units');
    }
};
