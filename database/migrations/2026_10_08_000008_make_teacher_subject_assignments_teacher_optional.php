<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_subject_assignments', function (Blueprint $table): void {
            $table->unsignedInteger('staff_ID')->nullable()->change();
        });

        // Every learner-selected elective needs a section assignment so it is
        // visible in the grade book even before an administrator assigns its teacher.
        $now = now();
        DB::table('enrollment_electives as electives')
            ->join('enrollments as enrollments', 'enrollments.enrollment_ID', '=', 'electives.enrollment_ID')
            ->whereNotNull('enrollments.section_ID')
            ->select([
                'enrollments.section_ID',
                'electives.subject_ID',
                'enrollments.SY_ID',
            ])
            ->distinct()
            ->orderBy('enrollments.section_ID')
            ->chunk(200, function ($rows) use ($now): void {
                DB::table('teacher_subject_assignments')->insertOrIgnore(
                    $rows->map(fn ($row): array => [
                        'section_ID' => $row->section_ID,
                        'subject_ID' => $row->subject_ID,
                        'staff_ID' => null,
                        'SY_ID' => $row->SY_ID,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        DB::table('teacher_subject_assignments')->whereNull('staff_ID')->delete();

        Schema::table('teacher_subject_assignments', function (Blueprint $table): void {
            $table->unsignedInteger('staff_ID')->nullable(false)->change();
        });
    }
};
