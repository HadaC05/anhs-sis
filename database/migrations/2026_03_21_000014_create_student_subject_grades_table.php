<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('student_subject_grades')) {
            Schema::create('student_subject_grades', function (Blueprint $table) {
                $table->increments('grade_ID');
                $table->unsignedInteger('enrollment_ID');
                $table->unsignedInteger('assignment_ID');
                $table->enum('grading_period', ['q1', 'q2', 'q3', 'q4', 'midterm', 'final']);
                $table->decimal('numeric_grade', 5, 2)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedInteger('posted_by');
                $table->timestamps();

                $table->foreign('enrollment_ID')->references('enrollment_ID')->on('enrollments')->cascadeOnDelete();
                $table->foreign('assignment_ID')->references('assignment_ID')->on('teacher_subject_assignments')->cascadeOnDelete();
                $table->foreign('posted_by')->references('staff_id')->on('staffs')->cascadeOnDelete();
                $table->unique(['enrollment_ID', 'assignment_ID', 'grading_period'], 'student_subject_grades_unique');
            });

            return;
        }

        if (Schema::hasColumn('student_subject_grades', 'posted_by')) {
            DB::statement('ALTER TABLE student_subject_grades MODIFY posted_by INT UNSIGNED NOT NULL');
        }

        $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'student_subject_grades')
            ->where('COLUMN_NAME', 'posted_by')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if (! $fkExists) {
            Schema::table('student_subject_grades', function (Blueprint $table) {
                $table->foreign('posted_by')->references('staff_id')->on('staffs')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_subject_grades');
    }
};
