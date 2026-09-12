<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_year_attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('SY_ID');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('school_days')->default(0);
            $table->timestamps();

            $table->foreign('SY_ID')->references('SY_ID')->on('academic_years')->cascadeOnDelete();
            $table->unique(['SY_ID', 'month'], 'academic_year_attendance_settings_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_year_attendance_settings');
    }
};
