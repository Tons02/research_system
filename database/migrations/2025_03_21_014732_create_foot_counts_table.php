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
        Schema::create('foot_counts', function (Blueprint $table) {
            $table->increments('id');
            $table->date("date");
            $table->time("time");
            $table->string("time_period", 2);
            $table->integer('total_male');
            $table->integer('total_female');
            $table->integer('grand_total');
            $table->unsignedInteger("surveyor_id")->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign("surveyor_id")
                ->references("id")
                ->on("users");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foot_counts');
    }
};
