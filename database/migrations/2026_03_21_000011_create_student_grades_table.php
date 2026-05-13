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
        if (Schema::hasTable('student_grades')) {
            return;
        }

        Schema::create('student_grades', function (Blueprint $table) {
            $table->increments('grade_ID');
            $table->unsignedInteger('enrollment_ID');
            $table->enum('grading_period', ['q1', 'q2', 'q3', 'q4', 'midterm', 'final']);
            $table->decimal('numeric_grade', 5, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('posted_by');
            $table->timestamps();

            $table->foreign('enrollment_ID')->references('enrollment_ID')->on('enrollments')->cascadeOnDelete();
            $table->foreign('posted_by')->references('staff_id')->on('staffs')->cascadeOnDelete();
            $table->unique(['enrollment_ID', 'grading_period'], 'student_grades_unique_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};
