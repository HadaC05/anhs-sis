<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->unsignedInteger('track_ID')->nullable()->after('curriculum_grade_level_ID');
            $table->foreign('track_ID')->references('track_ID')->on('tracks')->restrictOnDelete();
        });

        DB::table('enrollments')
            ->join('curriculum_grade_levels', 'curriculum_grade_levels.curriculum_ID', '=', 'enrollments.curriculum_grade_level_ID')
            ->join('clusters', 'clusters.cluster_ID', '=', 'curriculum_grade_levels.cluster_ID')
            ->whereNotNull('clusters.track_ID')
            ->select(['enrollments.enrollment_ID', 'clusters.track_ID'])
            ->orderBy('enrollments.enrollment_ID')
            ->each(function (object $row): void {
                DB::table('enrollments')->where('enrollment_ID', $row->enrollment_ID)->update([
                    'track_ID' => $row->track_ID,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropForeign(['track_ID']);
            $table->dropColumn('track_ID');
        });
    }
};
