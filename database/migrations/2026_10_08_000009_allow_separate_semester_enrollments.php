<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->unique(
                ['student_ID', 'SY_ID', 'curriculum_grade_level_ID'],
                'enrollments_unique_student_sy_offering',
            );
        });

        // MySQL may use the old unique index to support the student foreign
        // key. Create its replacement first so the constraint always has a
        // compatible index available.
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropUnique('enrollments_unique_student_sy');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->unique(['student_ID', 'SY_ID'], 'enrollments_unique_student_sy');
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropUnique('enrollments_unique_student_sy_offering');
        });
    }
};
