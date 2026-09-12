<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('section_ID');
            $table->unsignedInteger('SY_ID');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('school_days')->default(0);
            $table->timestamps();

            $table->foreign('section_ID')->references('section_ID')->on('sections')->cascadeOnDelete();
            $table->foreign('SY_ID')->references('SY_ID')->on('academic_years')->cascadeOnDelete();
            $table->unique(['section_ID', 'SY_ID', 'month'], 'section_attendance_settings_unique');
        });

        Schema::create('enrollment_monthly_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('enrollment_ID');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('days_present')->default(0);
            $table->unsignedSmallInteger('days_absent')->default(0);
            $table->unsignedSmallInteger('days_tardy')->default(0);
            $table->timestamps();

            $table->foreign('enrollment_ID')->references('enrollment_ID')->on('enrollments')->cascadeOnDelete();
            $table->unique(['enrollment_ID', 'month'], 'enrollment_monthly_attendance_unique');
        });

        Schema::create('section_sf2_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('section_ID');
            $table->unsignedInteger('SY_ID');
            $table->unsignedTinyInteger('report_month')->nullable();
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('status')->default('pending');
            $table->text('parse_notes')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('section_ID')->references('section_ID')->on('sections')->cascadeOnDelete();
            $table->foreign('SY_ID')->references('SY_ID')->on('academic_years')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_sf2_uploads');
        Schema::dropIfExists('enrollment_monthly_attendance');
        Schema::dropIfExists('section_attendance_settings');
    }
};
