<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_grade_term_unlocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('assignment_ID');
            $table->string('grading_period', 50);
            $table->unsignedInteger('unlocked_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->foreign('assignment_ID')->references('assignment_ID')->on('teacher_subject_assignments')->cascadeOnDelete();
            $table->foreign('unlocked_by')->references('staff_id')->on('staffs')->nullOnDelete();
            $table->unique(['assignment_ID', 'grading_period'], 'assignment_grade_term_unlocks_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_grade_term_unlocks');
    }
};
