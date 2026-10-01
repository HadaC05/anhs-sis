<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapeh_configurations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('curriculum_grade_level_ID');
            $table->unsignedInteger('SY_ID');
            $table->unsignedInteger('parent_curr_subj_ID');
            $table->string('mode', 16);
            $table->timestamps();
            $table->foreign('curriculum_grade_level_ID')->references('curriculum_ID')->on('curriculum_grade_levels')->restrictOnDelete();
            $table->foreign('SY_ID')->references('SY_ID')->on('academic_years')->restrictOnDelete();
            $table->foreign('parent_curr_subj_ID')->references('curr_subj_ID')->on('curriculum_subjects')->restrictOnDelete();
            $table->unique(['curriculum_grade_level_ID', 'SY_ID'], 'mapeh_offering_year_unique');
        });
        Schema::create('mapeh_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mapeh_configuration_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('curr_subj_ID');
            $table->string('key', 24);
            $table->foreign('curr_subj_ID')->references('curr_subj_ID')->on('curriculum_subjects')->restrictOnDelete();
            $table->unique(['mapeh_configuration_id', 'key'], 'mapeh_component_key_unique');
            $table->unique(['mapeh_configuration_id', 'curr_subj_ID'], 'mapeh_component_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapeh_components');
        Schema::dropIfExists('mapeh_configurations');
    }
};
