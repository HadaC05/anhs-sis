<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_observed_values')) {
            return;
        }

        Schema::create('student_observed_values', function (Blueprint $table) {
            $table->increments('observed_value_ID');
            $table->unsignedInteger('enrollment_ID');
            $table->string('statement_key', 50);
            $table->string('grading_period', 20);
            $table->enum('marking', ['AO', 'SO', 'RO', 'NO']);
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('posted_by');
            $table->timestamps();

            $table->foreign('enrollment_ID')->references('enrollment_ID')->on('enrollments')->cascadeOnDelete();
            $table->foreign('posted_by')->references('staff_id')->on('staffs')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('staff_id')->on('staffs')->nullOnDelete();
            $table->unique(['enrollment_ID', 'statement_key', 'grading_period'], 'student_observed_values_unique');
            $table->index(['status', 'enrollment_ID']);
            $table->index(['statement_key', 'grading_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_observed_values');
    }
};
