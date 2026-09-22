<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Support\EnrollmentDashboardData;
use App\Support\PlacementAssessmentAdvisor;
use App\Support\StudentGradeNotifier;
use App\Support\TeacherGradeNotifier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PrincipalDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $dashboardData = EnrollmentDashboardData::forRequest($request);

        return view('users.principal.dashboard', array_merge(
            $dashboardData,
            $this->proficiencyDashboardData($request, $dashboardData['activeYear'] ?? null)
        ));
    }

    public function gradeReleases(Request $request): View
    {
        $validated = $request->validate([
            'subject_id' => ['nullable', 'integer', 'exists:subjects,subject_ID'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,SY_ID'],
            'grade_level' => ['nullable', 'integer', 'exists:grade_level,grade_ID'],
            'term_id' => ['nullable', 'integer', 'exists:grading_terms,term_ID'],
            'semester' => ['nullable', 'in:first,second'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $gradeLevels = GradeLevel::query()->orderBy('grade_ID')->get();
        $selectedGradeLevel = isset($validated['grade_level']) ? (int) $validated['grade_level'] : null;
        $selectedGrade = $selectedGradeLevel ? $gradeLevels->firstWhere('grade_ID', $selectedGradeLevel) : null;
        $showSemesterFilter = in_array($selectedGrade?->grade_label, ['Grade 11', 'Grade 12'], true);
        $filters = [
            'subject_id' => isset($validated['subject_id']) ? (int) $validated['subject_id'] : null,
            'academic_year_id' => isset($validated['academic_year_id']) ? (int) $validated['academic_year_id'] : null,
            'grade_level' => $selectedGradeLevel,
            'term_id' => isset($validated['term_id']) ? (int) $validated['term_id'] : null,
            'semester' => $showSemesterFilter ? ($validated['semester'] ?? null) : null,
            'search' => trim($validated['search'] ?? ''),
        ];

        return view('users.principal.grade-releases', [
            'assignments' => $this->gradeReleaseAssignments($filters),
            'subjects' => Subject::query()->where('status', 'active')->orderBy('code')->orderBy('title')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->orderByDesc('SY_ID')->get(),
            'gradeLevels' => $gradeLevels,
            'terms' => GradingTerm::query()->orderBy('sort_order')->orderBy('term_ID')->get(),
            'filters' => $filters,
            'showSemesterFilter' => $showSemesterFilter,
        ]);
    }

    public function proficiencyLevels(Request $request): View
    {
        $validated = $request->validate([
            'subject_id' => ['nullable', 'integer', 'exists:subjects,subject_ID'],
            'academic_year_id' => ['nullable', 'string'],
            'grade_level' => ['nullable', 'integer', 'exists:grade_level,grade_ID'],
            'proficiency_level' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $levels = $this->proficiencyScale();
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->orderByDesc('SY_ID')->get();
        $selectedAcademicYear = $validated['academic_year_id'] ?? null;
        $selectedSubjectId = isset($validated['subject_id']) ? (int) $validated['subject_id'] : null;
        $selectedGradeLevel = $validated['grade_level'] ?? null;
        $selectedProficiencyLevel = $validated['proficiency_level'] ?? null;
        $search = trim($validated['search'] ?? '');
        $gradeLevels = GradeLevel::query()->orderBy('grade_ID')->get();
        $subjects = Subject::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->orderBy('title')
            ->get();

        abort_if(
            $selectedAcademicYear && $selectedAcademicYear !== 'all' && (
                ! ctype_digit((string) $selectedAcademicYear)
                || ! $academicYears->contains('SY_ID', (int) $selectedAcademicYear)
            ),
            404
        );

        abort_if(
            $selectedProficiencyLevel
            && $selectedProficiencyLevel !== 'pending'
            && ! collect($levels)->contains('label', $selectedProficiencyLevel),
            404
        );

        $selectedAcademicYearId = $selectedAcademicYear && $selectedAcademicYear !== 'all'
            ? (int) $selectedAcademicYear
            : ($selectedAcademicYear === 'all' ? null : $activeYear?->SY_ID);

        $selectedSubject = $selectedSubjectId
            ? $subjects->firstWhere('subject_ID', $selectedSubjectId)
            : null;

        $sections = collect();

        if ($selectedSubject) {
            $assignments = TeacherSubjectAssignment::query()
                ->with([
                    'section.cluster',
                    'section.gradeLevel',
                    'section.academicYear',
                    'curriculumSubject.subject',
                    'staff',
                ])
                ->whereHas('curriculumSubject', fn ($query) => $query->where('subject_ID', $selectedSubject->subject_ID))
                ->when($selectedAcademicYearId, fn ($query) => $query->where('SY_ID', $selectedAcademicYearId))
                ->when($selectedGradeLevel, function ($query) use ($selectedGradeLevel): void {
                    $query->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('grade_ID', $selectedGradeLevel));
                })
                ->get();

            $assignmentsBySection = $assignments->groupBy('section_ID');
            $sectionIds = $assignmentsBySection->keys()->filter()->values();
            $assignmentIds = $assignments->pluck('assignment_ID')->filter()->unique()->values();

            $enrollments = Enrollment::query()
                ->with(['student', 'grades' => fn ($query) => $query->whereIn('assignment_ID', $assignmentIds)])
                ->whereIn('section_ID', $sectionIds)
                ->when($selectedAcademicYearId, fn ($query) => $query->where('SY_ID', $selectedAcademicYearId))
                ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
                ->when($search !== '', function ($query) use ($search): void {
                    $query->whereHas('student', function ($studentQuery) use ($search): void {
                        $studentQuery->where('lrn', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
                })
                ->get()
                ->groupBy('section_ID');

            $sections = $assignmentsBySection
                ->map(function (Collection $sectionAssignments) use ($enrollments, $selectedProficiencyLevel, $search): ?array {
                    /** @var TeacherSubjectAssignment $primaryAssignment */
                    $primaryAssignment = $sectionAssignments->first();
                    $section = $primaryAssignment?->section;

                    if (! $section) {
                        return null;
                    }

                    $sectionAssignmentIds = $sectionAssignments->pluck('assignment_ID')->all();

                    $students = ($enrollments->get($section->section_ID) ?? collect())
                        ->map(function (Enrollment $enrollment) use ($sectionAssignmentIds): array {
                            $availableGrades = $enrollment->grades
                                ->whereIn('assignment_ID', $sectionAssignmentIds)
                                ->pluck('numeric_grade')
                                ->filter(fn ($grade): bool => $grade !== null && $grade !== '');

                            $subjectAverage = $availableGrades->isNotEmpty()
                                ? round((float) $availableGrades->avg())
                                : null;

                            return [
                                'lrn' => $enrollment->student?->lrn ?? 'N/A',
                                'name' => $enrollment->student
                                    ? trim($enrollment->student->last_name.', '.$enrollment->student->first_name.' '.($enrollment->student->middle_name ?? ''))
                                    : 'N/A',
                                'subject_average' => $subjectAverage,
                                'proficiency' => $this->proficiencyForAverage($subjectAverage),
                            ];
                        })
                        ->when($selectedProficiencyLevel, function (Collection $students) use ($selectedProficiencyLevel): Collection {
                            if ($selectedProficiencyLevel === 'pending') {
                                return $students->whereNull('subject_average')->values();
                            }

                            return $students->where('proficiency.label', $selectedProficiencyLevel)->values();
                        })
                        ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                        ->values();

                    if ($students->isEmpty() && ($selectedProficiencyLevel || $search !== '')) {
                        return null;
                    }

                    $cap = (int) ($section->capacity ?? 0);
                    $count = $students->count();
                    $pct = $cap > 0 ? min(100, (int) round(100 * $count / $cap)) : 0;
                    $teacher = $sectionAssignments->first(fn (TeacherSubjectAssignment $assignment): bool => $assignment->staff !== null)?->staff;

                    return [
                        'section' => $section,
                        'assignment' => $primaryAssignment,
                        'students' => $students,
                        'student_count' => $count,
                        'capacity' => $cap,
                        'capacity_percent' => $pct,
                        'teacher_name' => $teacher
                            ? trim($teacher->first_name.' '.$teacher->last_name)
                            : 'Unassigned',
                    ];
                })
                ->filter()
                ->sortBy(fn (array $row): string => strtolower($row['section']->name ?? ''))
                ->values();
        }

        $allStudents = $sections->flatMap(fn (array $row): Collection => $row['students']);
        $summary = collect($levels)
            ->mapWithKeys(fn (array $level): array => [
                $level['label'] => $allStudents->where('proficiency.label', $level['label'])->count(),
            ]);
        $incompleteCount = $allStudents->whereNull('subject_average')->count();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 12;
        $paginatedSections = new LengthAwarePaginator(
            $sections->forPage($page, $perPage)->values(),
            $sections->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('users.principal.proficiency-levels', [
            'activeYear' => $activeYear,
            'academicYears' => $academicYears,
            'gradeLevels' => $gradeLevels,
            'subjects' => $subjects,
            'levels' => $levels,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedSubjectId' => $selectedSubjectId,
            'selectedSubject' => $selectedSubject,
            'selectedGradeLevel' => $selectedGradeLevel,
            'selectedProficiencyLevel' => $selectedProficiencyLevel,
            'search' => $search,
            'sections' => $paginatedSections,
            'summary' => $summary,
            'incompleteCount' => $incompleteCount,
            'totalStudents' => $allStudents->count(),
        ]);
    }

    public function ageForGradeReport(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $alignment = $request->string('alignment', 'overage')->toString();
        $search = trim($request->string('search')->toString());
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $academicYearId = $request->string('academic_year_id')->toString();
        $perPage = (int) $request->integer('per_page', 15);
        $page = LengthAwarePaginator::resolveCurrentPage();

        $enrollments = Enrollment::query()
            ->with(['student', 'gradeLevel', 'section', 'academicYear', 'placementStatus'])
            ->whereIn('enrollment_status_ID', EnrollmentStatus::inProgressIds())
            ->when($academicYearId !== '' && $academicYearId !== 'all', function ($query) use ($academicYearId) {
                $query->where('SY_ID', $academicYearId);
            })
            ->when($academicYearId === '' && $activeYear, function ($query) use ($activeYear) {
                $query->where('SY_ID', $activeYear->SY_ID);
            })
            ->when($gradeId, function ($query) use ($gradeId) {
                $query->forGrade($gradeId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            })
            ->orderByGrade()
            ->orderBy('section_ID')
            ->orderBy('enrollment_ID')
            ->get();

        $reportRows = $enrollments
            ->map(function (Enrollment $enrollment) {
                $assessment = PlacementAssessmentAdvisor::assessmentForEnrollment($enrollment);

                if (! $assessment) {
                    return null;
                }

                return [
                    'enrollment' => $enrollment,
                    'student' => $enrollment->student,
                    'assessment' => $assessment,
                    'recommendation' => PlacementAssessmentAdvisor::forEnrollment($enrollment),
                ];
            })
            ->filter()
            ->values();

        $ageAlignment = PlacementAssessmentAdvisor::summarizeAgeAlignment(
            $reportRows->pluck('enrollment'),
        );

        $summary = [
            'all' => $ageAlignment['reviewed'],
            'appropriate' => $ageAlignment['appropriate'],
            'overage' => $ageAlignment['overage'],
            'underage' => $ageAlignment['underage'],
            'average_age' => $ageAlignment['average_age'],
            'marked_for_test' => $reportRows->filter(
                fn (array $row): bool => $row['enrollment']->isPlacementRecommended(),
            )->count(),
        ];

        $filteredRows = $alignment !== '' && $alignment !== 'all'
            ? $reportRows->where('assessment.status', $alignment)->values()
            : $reportRows;

        $paginatedRows = new LengthAwarePaginator(
            $filteredRows->forPage($page, max(1, $perPage))->values(),
            $filteredRows->count(),
            max(1, $perPage),
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('users.guidance.reports.age-for-grade', [
            'layout' => 'users.principal.layout',
            'reportRoute' => 'principal.reports.age-for-grade',
            'showEnrollmentAction' => false,
            'activeYear' => $activeYear,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'gradeLevels' => GradeLevel::options(),
            'rows' => $paginatedRows,
            'summary' => $summary,
            'ageAlignment' => $ageAlignment,
            'alignment' => $alignment,
            'gradeLevel' => $gradeLevel,
        ]);
    }

    public function showGradeRelease(Request $request, TeacherSubjectAssignment $assignment): View
    {
        $status = $request->string('status')->toString() === 'released' ? 'released' : 'approved';

        $assignment->load([
            'section.gradeLevel',
            'curriculumSubject.subject',
            'staff',
            'academicYear',
            'grades' => function ($query) use ($status): void {
                $query->whereStatus($status)
                    ->with(['enrollment.student.application', 'term'])
                    ->orderBy('term_ID');
            },
        ]);

        abort_if($assignment->grades->isEmpty(), 404);

        $periods = collect(GradingTerm::periodsForSection($assignment->section))
            ->filter(fn (array $period): bool => $assignment->grades->pluck('grading_period')->contains($period['key']))
            ->values()
            ->all();

        $rows = $assignment->grades
            ->groupBy('enrollment_ID')
            ->map(function ($grades) use ($periods): array {
                $firstGrade = $grades->first();
                $student = $firstGrade?->enrollment?->student;
                $application = $student?->application;
                $periodGrades = $grades->keyBy('grading_period');
                $values = [];
                $periodValues = [];

                foreach ($periods as $period) {
                    $value = $periodGrades->get($period['key'])?->numeric_grade;
                    if ($value !== null) {
                        $values[] = (float) $value;
                    }

                    $periodValues[$period['key']] = $value !== null ? number_format((float) $value, 2) : '-';
                }

                return [
                    'name' => $application
                        ? trim($application->last_name.', '.$application->first_name.' '.$application->middle_name)
                        : ($student?->name ?? 'N/A'),
                    'lrn' => $student?->lrn ?? 'N/A',
                    'period_values' => $periodValues,
                    'average' => count($values) ? round(array_sum($values) / count($values), 2) : null,
                    'remarks' => $grades->pluck('remarks')->filter()->unique()->implode(', '),
                ];
            })
            ->sortBy('name')
            ->values();

        return view('users.principal.grade-release-show', [
            'assignment' => $assignment,
            'periods' => $periods,
            'rows' => $rows,
            'status' => $status,
        ]);
    }

    public function releaseGrades(TeacherSubjectAssignment $assignment)
    {
        $students = $this->studentsWithApprovedGrades($assignment);

        $released = StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereStatus(GradeStatus::APPROVED)
            ->update(['grade_status_ID' => GradeStatus::idFor(GradeStatus::RELEASED)]);

        if ($released === 0) {
            return back()->with('status', 'No approved grades were found for release.');
        }

        TeacherGradeNotifier::released($assignment);
        StudentGradeNotifier::released($assignment, $students);

        return redirect()->route('principal.grade-releases')->with('status', "{$released} grade record(s) released to students.");
    }

    public function bulkReleaseGrades(Request $request)
    {
        $validated = $request->validate([
            'assignment_ids' => ['required', 'array', 'min:1'],
            'assignment_ids.*' => ['integer', 'exists:teacher_subject_assignments,assignment_ID'],
        ]);

        $assignments = TeacherSubjectAssignment::query()
            ->whereIn('assignment_ID', $validated['assignment_ids'])
            ->whereHas('grades', function ($query): void {
                $query->whereStatus(GradeStatus::APPROVED);
            })
            ->get();

        $studentsByAssignment = $assignments->mapWithKeys(fn (TeacherSubjectAssignment $assignment): array => [
            $assignment->assignment_ID => $this->studentsWithApprovedGrades($assignment),
        ]);

        $released = StudentSubjectGrade::query()
            ->whereIn('assignment_ID', $validated['assignment_ids'])
            ->whereStatus(GradeStatus::APPROVED)
            ->update(['grade_status_ID' => GradeStatus::idFor(GradeStatus::RELEASED)]);

        if ($released > 0) {
            $assignments->each(function (TeacherSubjectAssignment $assignment) use ($studentsByAssignment): void {
                TeacherGradeNotifier::released($assignment);
                StudentGradeNotifier::released($assignment, $studentsByAssignment->get($assignment->assignment_ID, collect()));
            });
        }

        return back()->with('status', "{$released} grade record(s) released to students.");
    }

    /**
     * Return each student whose approved grade will be released for an assignment.
     *
     * @return Collection<int, \App\Models\Student>
     */
    private function studentsWithApprovedGrades(TeacherSubjectAssignment $assignment): Collection
    {
        return StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereStatus(GradeStatus::APPROVED)
            ->with('studentSubject.enrollment.student')
            ->get()
            ->map(fn (StudentSubjectGrade $grade) => $grade->studentSubject?->enrollment?->student)
            ->filter()
            ->values();
    }

    /**
     * @param  array{subject_id: ?int, academic_year_id: ?int, grade_level: ?int, term_id: ?int, semester: ?string, search: string}  $filters
     */
    private function gradeReleaseAssignments(array $filters): Collection
    {
        return TeacherSubjectAssignment::query()
            ->with([
                'section.gradeLevel',
                'section.academicYear',
                'curriculumSubject.subject',
                'curriculumSubject.curriculumGradeLevel.gradingSemester',
                'staff',
                'grades' => function ($query) use ($filters): void {
                    $query->whereStatus([GradeStatus::APPROVED, GradeStatus::RELEASED])
                        ->when($filters['term_id'], fn ($gradeQuery) => $gradeQuery->where('term_ID', $filters['term_id']))
                        ->with(['gradeStatus', 'term']);
                },
            ])
            ->whereHas('grades', function ($query) use ($filters): void {
                $query->whereStatus([GradeStatus::APPROVED, GradeStatus::RELEASED])
                    ->when($filters['term_id'], fn ($gradeQuery) => $gradeQuery->where('term_ID', $filters['term_id']));
            })
            ->when($filters['subject_id'], fn ($query) => $query->whereHas('curriculumSubject', fn ($subjectQuery) => $subjectQuery->where('subject_ID', $filters['subject_id'])))
            ->when($filters['academic_year_id'], fn ($query) => $query->where('SY_ID', $filters['academic_year_id']))
            ->when($filters['grade_level'], fn ($query) => $query->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('grade_ID', $filters['grade_level'])))
            ->when($filters['semester'], fn ($query) => $query->whereHas('curriculumSubject.curriculumGradeLevel.gradingSemester', fn ($semesterQuery) => $semesterQuery->where('key', $filters['semester'])))
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('curriculumSubject.subject', fn ($subjectQuery) => $subjectQuery->where('code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"))
                        ->orWhereHas('staff', fn ($staffQuery) => $staffQuery->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('section_ID')
            ->get();
    }

    private function proficiencyScale(): array
    {
        return [
            [
                'range' => '90-100',
                'label' => 'Advanced',
                'description' => 'The learner demonstrates a high level of understanding and can independently apply knowledge and skills in different situations.',
            ],
            [
                'range' => '85-89',
                'label' => 'Proficient',
                'description' => 'The learner demonstrates the expected knowledge and skills independently.',
            ],
            [
                'range' => '80-84',
                'label' => 'Approaching Proficiency',
                'description' => 'The learner demonstrates the basic knowledge and skills but still needs improvement in some competencies.',
            ],
            [
                'range' => '75-79',
                'label' => 'Developing',
                'description' => 'The learner demonstrates the minimum required competencies but still requires guidance and support.',
            ],
            [
                'range' => 'Below 75',
                'label' => 'Beginning',
                'description' => 'The learner has not yet mastered the required competencies and needs significant intervention.',
            ],
        ];
    }

    private function proficiencyForAverage(?float $average): ?array
    {
        if ($average === null) {
            return null;
        }

        return collect($this->proficiencyScale())
            ->first(function (array $level) use ($average): bool {
                return match ($level['label']) {
                    'Advanced' => $average >= 90 && $average <= 100,
                    'Proficient' => $average >= 85 && $average <= 89,
                    'Approaching Proficiency' => $average >= 80 && $average <= 84,
                    'Developing' => $average >= 75 && $average <= 79,
                    'Beginning' => $average < 75,
                    default => false,
                };
            });
    }

    private function proficiencyDashboardData(Request $request, ?AcademicYear $activeYear): array
    {
        $levels = $this->proficiencyScale();
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->orderByDesc('SY_ID')->get();
        $selectedYearId = $request->integer('proficiency_academic_year_id') ?: $activeYear?->SY_ID;
        $selectedYear = $selectedYearId
            ? $academicYears->firstWhere('SY_ID', $selectedYearId)
            : $activeYear;

        if (! $selectedYear && $activeYear) {
            $selectedYear = $activeYear;
            $selectedYearId = $activeYear->SY_ID;
        }

        $previousYear = $selectedYear
            ? $academicYears
                ->filter(fn (AcademicYear $year): bool => $year->SY_ID !== $selectedYear->SY_ID)
                ->first(function (AcademicYear $year) use ($selectedYear): bool {
                    if ($year->start_date && $selectedYear->start_date) {
                        return $year->start_date->lt($selectedYear->start_date);
                    }

                    return $year->SY_ID < $selectedYear->SY_ID;
                })
            : null;

        $comparisonYearId = $request->integer('proficiency_compare_year_id') ?: $previousYear?->SY_ID;
        $comparisonYear = $comparisonYearId
            ? $academicYears->firstWhere('SY_ID', $comparisonYearId)
            : null;

        $selectedGradeLevel = $request->string('proficiency_grade_level')->toString();
        $selectedGradeId = GradeLevel::idForValue($selectedGradeLevel);

        $distribution = $this->proficiencyDistributionFor($levels, $selectedYear?->SY_ID, $selectedGradeId);
        $comparisonDistribution = $comparisonYear && $comparisonYear->SY_ID !== $selectedYear?->SY_ID
            ? $this->proficiencyDistributionFor($levels, $comparisonYear->SY_ID, $selectedGradeId)
            : collect($levels)->map(fn (array $level): array => [
                'label' => $level['label'],
                'range' => $level['range'],
                'total' => 0,
            ])->values();

        return [
            'academicYears' => $academicYears,
            'proficiencyDistribution' => $distribution,
            'proficiencyComparisonDistribution' => $comparisonDistribution,
            'proficiencyReviewedCount' => $distribution->sum('total'),
            'proficiencyComparisonReviewedCount' => $comparisonDistribution->sum('total'),
            'proficiencySelectedAcademicYear' => $selectedYear?->SY_ID,
            'proficiencySelectedAcademicYearLabel' => $selectedYear?->school_year ?? 'Not set',
            'proficiencyComparisonAcademicYear' => $comparisonYear && $comparisonYear->SY_ID !== $selectedYear?->SY_ID
                ? $comparisonYear->SY_ID
                : null,
            'proficiencyComparisonAcademicYearLabel' => $comparisonYear && $comparisonYear->SY_ID !== $selectedYear?->SY_ID
                ? $comparisonYear->school_year
                : null,
            'proficiencyGradeLevel' => $selectedGradeLevel,
        ];
    }

    private function proficiencyDistributionFor(array $levels, ?int $academicYearId, ?int $gradeId)
    {
        $students = Enrollment::query()
            ->with(['grades'])
            ->when($academicYearId, fn ($query) => $query->where('SY_ID', $academicYearId))
            ->when($gradeId, fn ($query) => $query->forGrade($gradeId))
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->get()
            ->map(function (Enrollment $enrollment): ?array {
                $subjectFinals = $enrollment->grades
                    ->groupBy('assignment_ID')
                    ->map(function ($grades): ?float {
                        $availableGrades = $grades
                            ->pluck('numeric_grade')
                            ->filter(fn ($grade): bool => $grade !== null && $grade !== '');

                        return $availableGrades->isNotEmpty()
                            ? round((float) $availableGrades->avg())
                            : null;
                    })
                    ->filter(fn ($grade): bool => $grade !== null);

                if ($subjectFinals->isEmpty()) {
                    return null;
                }

                $generalAverage = round((float) $subjectFinals->avg());

                return $this->proficiencyForAverage($generalAverage);
            })
            ->filter();

        return collect($levels)
            ->map(function (array $level) use ($students): array {
                return [
                    'label' => $level['label'],
                    'range' => $level['range'],
                    'total' => $students->where('label', $level['label'])->count(),
                ];
            })
            ->values();
    }
}
