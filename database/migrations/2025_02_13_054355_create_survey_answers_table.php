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
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger("target_location_id")->index();
            $table->string('name');
            $table->string('age');
            $table->enum("gender", ["male", "female"]);
            $table->text('address');
            $table->string('contact_number');
            $table->string('date');
            $table->integer('family_size');
            $table->string('income_class')->nullable();
            $table->string('sub_income_class')->nullable();
            $table->string('monthly_utility_expenses');
            $table->string('sub_monthly_utility_expenses')->nullable();
            $table->string('educational_attainment');
            $table->string('employment_status');
            $table->string('occupation');
            $table->string('structure_of_house');
            $table->string('ownership_of_house');
            $table->string('house_rent')->nullable();
            $table->json('questionnaire_answer');
            $table->unsignedInteger("surveyor_id")->index();


            // relationship on target location
            $table->foreign("target_location_id")
            ->references("id")
            ->on("target_locations");

            // relationship on users
            $table->foreign("surveyor_id")
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
        Schema::dropIfExists('survey_answers');
    }
};
