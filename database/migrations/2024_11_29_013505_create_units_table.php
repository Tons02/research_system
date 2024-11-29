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
        Schema::create('units', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('sync_id')->unique();
            $table->string('unit_code');
            $table->string('unit_name');
            $table->bigInteger('department_id')->index();
            $table->foreign("department_id")
            ->references("sync_id")
            ->on("departments")
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
        Schema::dropIfExists('units');
    }
};
