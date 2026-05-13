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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->increments('enrollment_ID');
            $table->unsignedBigInteger('student_ID');
            $table->unsignedInteger('section_ID');
            $table->unsignedInteger('SY_ID');
            $table->unsignedInteger('cluster_ID');
            $table->unsignedInteger('grade_ID');
            $table->enum('semester', ['first', 'second']);
            $table->enum('learner_type', ['regular', 'transferee', 'balik_aral', 'returnee']);
            $table->enum('enrollment_status', ['pending', 'enrolled', 'completed', 'withdrawn', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->foreign('student_ID')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('section_ID')->references('section_ID')->on('sections')->restrictOnDelete();
            $table->foreign('SY_ID')->references('SY_ID')->on('academic_years')->restrictOnDelete();
            $table->foreign('cluster_ID')->references('cluster_ID')->on('clusters')->restrictOnDelete();
            $table->foreign('grade_ID')->references('grade_ID')->on('grade_level')->restrictOnDelete();
            $table->unique(['student_ID', 'section_ID', 'SY_ID', 'semester'], 'enrollments_unique_student_section_sy_semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
