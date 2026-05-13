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
        Schema::create('sections', function (Blueprint $table) {
            $table->increments('section_ID');
            $table->string('name');
            $table->unsignedInteger('cluster_ID');
            $table->unsignedInteger('grade_ID');
            $table->unsignedInteger('staff_ID')->nullable();
            $table->unsignedInteger('SY_ID');
            $table->unsignedInteger('curriculum_ID');
            $table->string('room')->nullable();
            $table->unsignedInteger('capacity');
            $table->timestamps();

            $table->foreign('cluster_ID')->references('cluster_ID')->on('clusters')->restrictOnDelete();
            $table->foreign('grade_ID')->references('grade_ID')->on('grade_level')->restrictOnDelete();
            $table->foreign('staff_ID')->references('staff_id')->on('staffs')->nullOnDelete();
            $table->foreign('SY_ID')->references('SY_ID')->on('academic_years')->restrictOnDelete();
            $table->foreign('curriculum_ID')->references('curriculum_ID')->on('curriculum')->restrictOnDelete();
            $table->unique(['name', 'SY_ID'], 'sections_name_sy_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
