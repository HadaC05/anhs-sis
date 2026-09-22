<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\LearnerType;
use App\Models\Section;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $staff = $request->user();
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $syId = $activeYear?->SY_ID;

        $advisorySectionIds = collect();
        $teachingSectionIds = collect();
        $sectionIds = collect();

        if ($staff) {
            $advisorySectionIds = Section::query()
                ->when($syId, fn ($query) => $query->where('SY_ID', $syId))
                ->where('staff_ID', $staff->staff_id)
                ->pluck('section_ID');

            $teachingSectionIds = TeacherSubjectAssignment::query()
                ->when($syId, fn ($query) => $query->where('SY_ID', $syId))
                ->where('staff_ID', $staff->staff_id)
                ->pluck('section_ID');

            $sectionIds = $advisorySectionIds
                ->merge($teachingSectionIds)
                ->unique()
                ->values();
        }

        $baseQuery = Enrollment::query()
            ->when($syId, fn ($query) => $query->where('SY_ID', $syId))
            ->when(
                $sectionIds->isNotEmpty(),
                fn ($query) => $query->whereIn('section_ID', $sectionIds),
                fn ($query) => $query->whereRaw('1 = 0')
            );

        $activeEnrolleeQuery = (clone $baseQuery)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());

        $totalStudents = (clone $activeEnrolleeQuery)->count();
        $enrolledCount = (clone $baseQuery)->where('enrollment_status_ID', EnrollmentStatus::idFor(EnrollmentStatus::ENROLLED))->count();
        $temporaryCount = (clone $baseQuery)->where('enrollment_status_ID', EnrollmentStatus::idFor(EnrollmentStatus::TEMPORARILY_ENROLLED))->count();
        $transfereeCount = (clone $activeEnrolleeQuery)->where('learner_type_ID', LearnerType::idFor(LearnerType::TRANSFEREE))->count();
        $balikAralCount = (clone $activeEnrolleeQuery)->where('learner_type_ID', LearnerType::idFor(LearnerType::BALIK_ARAL))->count();

        $genderSectionId = $request->string('gender_section_id')->toString();
        $genderSectionId = $sectionIds->contains((int) $genderSectionId) ? (int) $genderSectionId : null;

        $genderDistribution = (clone $activeEnrolleeQuery)
            ->when($genderSectionId, fn ($query) => $query->where('section_ID', $genderSectionId))
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

        $sectionDistribution = Section::query()
            ->with(['gradeLevel', 'cluster'])
            ->whereIn('section_ID', $sectionIds)
            ->withCount(['enrollments as active_enrollments_count' => function ($query): void {
                $query->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->orderByDesc('active_enrollments_count')
            ->orderBy('name')
            ->get()
            ->map(fn (Section $section): array => [
                'label' => $section->name,
                'total' => (int) ($section->active_enrollments_count ?? 0),
                'is_advisory' => $advisorySectionIds->contains($section->section_ID),
            ])
            ->values()
            ->all();

        $enrollmentGradeLevel = $request->string('enrollment_grade_level')->toString();
        $enrollmentGradeId = GradeLevel::idForValue($enrollmentGradeLevel);

        $enrolledStatusId = EnrollmentStatus::idFor(EnrollmentStatus::ENROLLED);
        $temporaryStatusId = EnrollmentStatus::idFor(EnrollmentStatus::TEMPORARILY_ENROLLED);
        $enrollmentStatusColumn = DB::connection()
            ->getQueryGrammar()
            ->wrap('enrollments.enrollment_status_ID');

        $enrollmentByGrade = DB::table('enrollments')
            ->join('curriculum_grade_levels', 'enrollments.curriculum_grade_level_ID', '=', 'curriculum_grade_levels.curriculum_ID')
            ->join('grade_level', 'curriculum_grade_levels.grade_ID', '=', 'grade_level.grade_ID')
            ->when($syId, fn ($query) => $query->where('enrollments.SY_ID', $syId))
            ->when(
                $sectionIds->isNotEmpty(),
                fn ($query) => $query->whereIn('enrollments.section_ID', $sectionIds->all()),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->whereIn('enrollments.enrollment_status_ID', EnrollmentStatus::activeIds())
            ->when($enrollmentGradeId, fn ($query) => $query->where('curriculum_grade_levels.grade_ID', $enrollmentGradeId))
            ->select('grade_level.grade_ID')
            ->selectRaw('grade_level.grade_label as label')
            ->selectRaw("SUM(CASE WHEN {$enrollmentStatusColumn} = ? THEN 1 ELSE 0 END) as enrolled", [$enrolledStatusId])
            ->selectRaw("SUM(CASE WHEN {$enrollmentStatusColumn} = ? THEN 1 ELSE 0 END) as temporary", [$temporaryStatusId])
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

        $recentStudents = (clone $baseQuery)
            ->with(['student.application', 'section.gradeLevel', 'gradeLevel', 'cluster', 'learnerType'])
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $sectionsCount = $sectionIds->count();
        $advisoryCount = $advisorySectionIds->count();
        $subjectsCount = $staff
            ? TeacherSubjectAssignment::query()
                ->where('staff_ID', $staff->staff_id)
                ->when($syId, fn ($query) => $query->where('SY_ID', $syId))
                ->count()
            : 0;

        $teacherSections = Section::query()
            ->with(['gradeLevel', 'cluster', 'academicYear'])
            ->whereIn('section_ID', $sectionIds)
            ->withCount(['enrollments as active_enrollments_count' => function ($query): void {
                $query->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->orderBy('grade_ID')
            ->orderBy('name')
            ->get();

        return view('users.teacher.dashboard', [
            'staff' => $staff,
            'activeYear' => $activeYear,
            'totalStudents' => $totalStudents,
            'enrolledCount' => $enrolledCount,
            'temporaryCount' => $temporaryCount,
            'transfereeCount' => $transfereeCount,
            'balikAralCount' => $balikAralCount,
            'genderDistribution' => $genderDistribution,
            'sectionDistribution' => $sectionDistribution,
            'recentStudents' => $recentStudents,
            'genderSectionId' => $genderSectionId ? (string) $genderSectionId : '',
            'enrollmentGradeLevel' => $enrollmentGradeLevel,
            'enrollmentByGrade' => $enrollmentByGrade,
            'enrollmentGradeSummary' => $enrollmentGradeSummary,
            'gradeLevels' => GradeLevel::options(),
            'sectionsCount' => $sectionsCount,
            'advisoryCount' => $advisoryCount,
            'subjectsCount' => $subjectsCount,
            'teacherSections' => $teacherSections,
            'advisorySectionIds' => $advisorySectionIds,
        ]);
    }
}
