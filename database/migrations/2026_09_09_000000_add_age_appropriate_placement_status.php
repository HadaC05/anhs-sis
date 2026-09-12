<?php

use App\Models\Enrollment;
use App\Models\PlacementStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('placement_statuses')) {
            return;
        }

        $now = now();

        DB::table('placement_statuses')->updateOrInsert(
            ['slug' => PlacementStatus::AGE_APPROPRIATE],
            ['name' => 'Age Appropriate', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
        );
        PlacementStatus::clearOptionsCache();

        $ageAppropriateId = DB::table('placement_statuses')
            ->where('slug', PlacementStatus::AGE_APPROPRIATE)
            ->value('placement_status_ID');
        $pendingId = DB::table('placement_statuses')
            ->where('slug', PlacementStatus::PENDING)
            ->value('placement_status_ID');

        if (Schema::hasTable('enrollments') && $ageAppropriateId !== null && $pendingId !== null) {
            Enrollment::query()
                ->with(['student', 'academicYear', 'gradeLevel', 'placementStatus'])
                ->where('placement_status_ID', $pendingId)
                ->chunkById(100, function ($enrollments): void {
                    foreach ($enrollments as $enrollment) {
                        $enrollment->update([
                            'placement_status' => $enrollment->requiresPlacementAssessment()
                                ? PlacementStatus::RECOMMENDED
                                : PlacementStatus::AGE_APPROPRIATE,
                        ]);
                    }
                }, 'enrollment_ID');
        }

        PlacementStatus::clearOptionsCache();
    }

    public function down(): void
    {
        if (! Schema::hasTable('placement_statuses')) {
            return;
        }

        $ageAppropriateId = DB::table('placement_statuses')
            ->where('slug', PlacementStatus::AGE_APPROPRIATE)
            ->value('placement_status_ID');
        $pendingId = DB::table('placement_statuses')
            ->where('slug', PlacementStatus::PENDING)
            ->value('placement_status_ID');

        if (Schema::hasTable('enrollments') && $ageAppropriateId !== null && $pendingId !== null) {
            DB::table('enrollments')
                ->where('placement_status_ID', $ageAppropriateId)
                ->update(['placement_status_ID' => $pendingId]);
        }

        DB::table('placement_statuses')->where('slug', PlacementStatus::AGE_APPROPRIATE)->delete();
        PlacementStatus::clearOptionsCache();
    }
};
