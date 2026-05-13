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
        Schema::create('teacher_subject_assignments', function (Blueprint $table) {
            $table->increments('assignment_ID');
            $table->unsignedInteger('section_ID');
            $table->unsignedInteger('curr_subj_ID');
            $table->unsignedInteger('staff_ID');
            $table->unsignedInteger('SY_ID');
            $table->timestamps();

            $table->foreign('section_ID')->references('section_ID')->on('sections')->cascadeOnDelete();
            $table->foreign('curr_subj_ID')->references('curr_subj_ID')->on('curriculum_subjects')->cascadeOnDelete();
            $table->foreign('staff_ID')->references('staff_id')->on('staffs')->cascadeOnDelete();
            $table->foreign('SY_ID')->references('SY_ID')->on('academic_years')->cascadeOnDelete();
            $table->unique(['section_ID', 'curr_subj_ID'], 'teacher_subject_assignments_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_subject_assignments');
    }
};
