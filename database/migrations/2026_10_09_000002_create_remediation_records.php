<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('remediation_cases')) {
            // MySQL can retain a table when a CREATE TABLE migration fails while
            // adding a later foreign key. Resume that partial migration safely.
            Schema::table('remediation_cases', function (Blueprint $table): void {
                $table->unsignedInteger('started_by')->change();
                $table->unsignedInteger('approved_by')->nullable()->change();
            });
            Schema::table('remediation_cases', function (Blueprint $table): void {
                $table->foreign('started_by')->references('staff_id')->on('staffs')->restrictOnDelete();
                $table->foreign('approved_by')->references('staff_id')->on('staffs')->nullOnDelete();
            });
        } else {
            Schema::create('remediation_cases', function (Blueprint $table): void {
                $table->id('remediation_case_ID');
                $table->unsignedInteger('enrollment_ID')->unique();
                $table->string('status', 40)->default('in_progress');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->unsignedInteger('started_by');
                $table->unsignedInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('enrollment_ID')->references('enrollment_ID')->on('enrollments')->cascadeOnDelete();
                $table->foreign('started_by')->references('staff_id')->on('staffs')->restrictOnDelete();
                $table->foreign('approved_by')->references('staff_id')->on('staffs')->nullOnDelete();
            });
        }

        Schema::create('remediation_subjects', function (Blueprint $table): void {
            $table->id('remediation_subject_ID');
            $table->unsignedBigInteger('remediation_case_ID');
            $table->unsignedInteger('subject_ID');
            $table->decimal('original_final_grade', 5, 2);
            $table->decimal('remedial_class_mark', 5, 2)->nullable();
            $table->decimal('recomputed_final_grade', 5, 2)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->timestamps();

            $table->foreign('remediation_case_ID')->references('remediation_case_ID')->on('remediation_cases')->cascadeOnDelete();
            $table->foreign('subject_ID')->references('subject_ID')->on('subjects')->restrictOnDelete();
            $table->unique(['remediation_case_ID', 'subject_ID'], 'remediation_case_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remediation_subjects');
        Schema::dropIfExists('remediation_cases');
    }
};
