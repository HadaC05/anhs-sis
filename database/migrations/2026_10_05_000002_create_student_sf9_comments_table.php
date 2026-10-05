<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_sf9_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('enrollment_ID');
            $table->foreign('enrollment_ID')->references('enrollment_ID')->on('enrollments')->cascadeOnDelete();
            $table->string('grading_period');
            $table->string('comment', 240);
            $table->unsignedInteger('posted_by')->nullable();
            $table->foreign('posted_by')->references('staff_id')->on('staffs')->nullOnDelete();
            $table->timestamps();
            $table->unique(['enrollment_ID', 'grading_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_sf9_comments');
    }
};
