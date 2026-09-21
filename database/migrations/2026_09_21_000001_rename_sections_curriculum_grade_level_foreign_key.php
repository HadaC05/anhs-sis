<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Make the section offering key explicit, matching enrollments. */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table): void {
            $table->renameColumn('curriculum_ID', 'curriculum_grade_level_ID');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table): void {
            $table->renameColumn('curriculum_grade_level_ID', 'curriculum_ID');
        });
    }
};
