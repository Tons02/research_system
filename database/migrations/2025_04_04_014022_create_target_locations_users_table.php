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
        Schema::create('target_locations_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger("target_location_id")->index();
            $table->unsignedInteger("user_id")->index();
            $table->integer("response_limit");
            $table->boolean("is_done");

            $table->foreign('target_location_id')
                  ->references('id')
                  ->on('target_locations')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_locations_users');
    }
};
