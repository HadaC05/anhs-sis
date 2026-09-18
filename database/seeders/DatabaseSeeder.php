<?php

namespace Database\Seeders;

use App\Models\RejectionReason;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(DefaultNonStudentUsersSeeder::class);
        $this->call(ClusterSeeder::class);
        $this->call(PreferredCourseSeeder::class);
        $this->call(GradeLevelSeeder::class);
        $this->call(DataStatusSeeder::class);
        $this->call(GradingSemesterSeeder::class);
        $this->call(SubjectTypeSeeder::class);
        $this->call(SubjectSeeder::class);
        $this->call(CurriculumSeeder::class);
        $this->call(MonthSeeder::class);
        $this->call(AcademicYearSeeder::class);
        $this->call(SectionSeeder::class);
        $this->call(TeacherSubjectAssignmentSeeder::class);
        $this->call(MovementReasonSeeder::class);
        $this->call(DocumentReturnReasonSeeder::class);
        $this->call(DocumentTypeSeeder::class);
        $this->call(DocumentStatusSeeder::class);
        $this->call(ReligionSeeder::class);
        $this->call(EnrollmentStatusSeeder::class);
        $this->call(PlacementStatusSeeder::class);
        $this->call(PromotionStatusSeeder::class);
        $this->call(LearnerTypeSeeder::class);
        $this->call(GradingPeriodStatusSeeder::class);
        $this->call(GradeStatusSeeder::class);
        $this->call(NotificationTypeSeeder::class);

        foreach (
            [
                ['reason_name' => 'LRN not found in LIS', 'description' => 'No matching learner record was found in LIS.'],
                ['reason_name' => 'Name mismatch with LIS', 'description' => 'Submitted name does not match LIS record.'],
                ['reason_name' => 'Birthdate mismatch with LIS', 'description' => 'Submitted birthdate does not match LIS record.'],
                ['reason_name' => 'Duplicate approved record', 'description' => 'An account for this learner already exists.'],
            ] as $reason
        ) {
            RejectionReason::query()->firstOrCreate(
                ['reason_name' => $reason['reason_name']],
                ['description' => $reason['description'], 'status' => true],
            );
        }
    }
}
