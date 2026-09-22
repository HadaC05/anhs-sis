<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\LearnerType;
use App\Models\PlacementStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentDashboardData
{
    /**
     * @return array<string, mixed>
     */
    public static function forRequest(Request $request): array
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $syId = $activeYear?->SY_ID;

        $baseQuery = Enrollment::query()
            ->when($syId, fn ($q) => $q->where('SY_ID', $syId));

        $activeEnrolleeQuery = (clone $baseQuery)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());

        $genderGradeLevel = $request->string('gender_grade_level')->toString();
        $genderGradeId = GradeLevel::idForValue($genderGradeLevel);

        $genderDistribution = (clone $activeEnrolleeQuery)
            ->when($genderGradeId, fn ($query) => $query->forGrade($genderGradeId))
            ->join('students', 'enrollments.student_ID', '=', 'students.id')
            ->selectRaw("CASE WHEN students.sex = 'male' THEN 'Male' WHEN students.sex = 'female' THEN 'Female' ELSE 'Unspecified' END as label")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw("CASE WHEN students.sex = 'male' THEN 'Male' WHEN students.sex = 'female' THEN 'Female' ELSE 'Unspecified' END")
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $clusterDistribution = DB::table('enrollments')
            ->leftJoin('curriculum_grade_levels', 'enrollments.curriculum_grade_level_ID', '=', 'curriculum_grade_levels.curriculum_ID')
            ->leftJoin('clusters', 'curriculum_grade_levels.cluster_ID', '=', 'clusters.cluster_ID')
            ->when($syId, fn ($query) => $query->where('enrollments.SY_ID', $syId))
            ->whereIn('enrollments.enrollment_status_ID', EnrollmentStatus::activeIds())
            ->selectRaw("COALESCE(clusters.name, 'Junior High School') as label")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw("COALESCE(clusters.name, 'Junior High School')")
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $enrollmentGradeLevel = $request->string('enrollment_grade_level')->toString();
        $enrollmentGradeId = GradeLevel::idForValue($enrollmentGradeLevel);

        $enrolledStatusId = EnrollmentStatus::idFor(EnrollmentStatus::ENROLLED);
        $temporaryStatusId = EnrollmentStatus::idFor(EnrollmentStatus::TEMPORARILY_ENROLLED);

        $enrollmentByGrade = DB::table('enrollments')
            ->join('curriculum_grade_levels', 'enrollments.curriculum_grade_level_ID', '=', 'curriculum_grade_levels.curriculum_ID')
            ->join('grade_level', 'curriculum_grade_levels.grade_ID', '=', 'grade_level.grade_ID')
            ->when($syId, fn ($query) => $query->where('enrollments.SY_ID', $syId))
            ->whereIn('enrollments.enrollment_status_ID', EnrollmentStatus::activeIds())
            ->when($enrollmentGradeId, fn ($query) => $query->where('curriculum_grade_levels.grade_ID', $enrollmentGradeId))
            ->select('grade_level.grade_ID')
            ->selectRaw('grade_level.grade_label as label')
            ->selectRaw('SUM(CASE WHEN enrollments.enrollment_status_ID = ? THEN 1 ELSE 0 END) as enrolled', [$enrolledStatusId])
            ->selectRaw('SUM(CASE WHEN enrollments.enrollment_status_ID = ? THEN 1 ELSE 0 END) as temporary', [$temporaryStatusId])
            ->groupBy('grade_level.grade_ID', 'grade_level.grade_label')
            ->orderBy('grade_level.grade_ID')
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->label,
                'enrolled' => (int) $row->enrolled,
                'temporary' => (int) $row->temporary,
                'total' => (int) $row->enrolled + (int) $row->temporary,
            ])
            ->values()
            ->all();

        $enrollmentGradeSummary = [
            'enrolled' => array_sum(array_column($enrollmentByGrade, 'enrolled')),
            'temporary' => array_sum(array_column($enrollmentByGrade, 'temporary')),
            'total' => array_sum(array_column($enrollmentByGrade, 'total')),
        ];

        $ageGradeLevel = $request->string('age_grade_level')->toString();
        $ageGradeId = GradeLevel::idForValue($ageGradeLevel);

        $ageReviewEnrollments = Enrollment::query()
            ->with(['student', 'academicYear', 'gradeLevel'])
            ->when($syId, fn ($query) => $query->where('SY_ID', $syId))
            ->whereIn('enrollment_status_ID', EnrollmentStatus::inProgressIds())
            ->when($ageGradeId, fn ($query) => $query->forGrade($ageGradeId))
            ->get();

        return [
            'activeYear' => $activeYear,
            'totalEnrollees' => (clone $activeEnrolleeQuery)->count(),
            'enrolledCount' => (clone $baseQuery)->where('enrollment_status_ID', EnrollmentStatus::idFor(EnrollmentStatus::ENROLLED))->count(),
            'temporaryCount' => (clone $baseQuery)->where('enrollment_status_ID', EnrollmentStatus::idFor(EnrollmentStatus::TEMPORARILY_ENROLLED))->count(),
            'pendingCount' => (clone $baseQuery)->where('enrollment_status_ID', EnrollmentStatus::idFor(EnrollmentStatus::PENDING))->count(),
            'transfereeCount' => (clone $activeEnrolleeQuery)->where('learner_type_ID', LearnerType::idFor(LearnerType::TRANSFEREE))->count(),
            'balikAralCount' => (clone $activeEnrolleeQuery)->where('learner_type_ID', LearnerType::idFor(LearnerType::BALIK_ARAL))->count(),
            'genderDistribution' => $genderDistribution,
            'clusterDistribution' => $clusterDistribution,
            'recentEnrollments' => (clone $baseQuery)
                ->with(['student.application', 'section.gradeLevel', 'gradeLevel', 'cluster', 'preferredCourse', 'enrollmentStatus', 'learnerType', 'placementStatus'])
                ->latest('created_at')
                ->limit(5)
                ->get(),
            'ageAlignment' => PlacementAssessmentAdvisor::summarizeAgeAlignment($ageReviewEnrollments),
            'ageGradeLevel' => $ageGradeLevel,
            'genderGradeLevel' => $genderGradeLevel,
            'enrollmentGradeLevel' => $enrollmentGradeLevel,
            'enrollmentByGrade' => $enrollmentByGrade,
            'enrollmentGradeSummary' => $enrollmentGradeSummary,
            'gradeLevels' => GradeLevel::options(),
            'placementTestMarkedCount' => (clone $baseQuery)
                ->where('placement_status_ID', PlacementStatus::recommendedId())
                ->count(),
        ];
    }
}
