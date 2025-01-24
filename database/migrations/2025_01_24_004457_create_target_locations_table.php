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
            $table->string('target_location');
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
