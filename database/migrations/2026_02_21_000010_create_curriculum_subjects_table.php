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
        Schema::create('curriculum_subjects', function (Blueprint $table) {
            $table->increments('curr_subj_ID');
            $table->unsignedInteger('curriculum_ID');
            $table->unsignedInteger('subject_ID');
            $table->unsignedInteger('cluster_ID');
            $table->enum('grade_level', ['grade_11', 'grade_12']);
            $table->enum('semester', ['first', 'second']);
            $table->timestamps();

            $table->foreign('curriculum_ID')->references('curriculum_ID')->on('curriculum')->cascadeOnDelete();
            $table->foreign('subject_ID')->references('subject_ID')->on('subjects')->cascadeOnDelete();
            $table->foreign('cluster_ID')->references('cluster_ID')->on('clusters')->cascadeOnDelete();
            $table->unique(['curriculum_ID', 'subject_ID', 'grade_level', 'semester'], 'curriculum_subjects_unique_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_subjects');
    }
};
