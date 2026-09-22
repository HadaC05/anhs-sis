<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\GradeReturnReason;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\LearnerType;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentObservedValue;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use App\Support\AssignmentGradeTermUnlocker;
use App\Support\EnrollmentDashboardData;
use App\Support\LearnerPermanentRecordBuilder;
use App\Support\Sf9AttendanceSummary;
use App\Support\Sf9ReportCardBuilder;
use App\Support\TeacherGradeNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrarDashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('users.registrar.dashboard', EnrollmentDashboardData::forRequest($request));
    }

    public function students(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $learnerType = $request->string('learner_type')->toString();
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $academicYearId = $request->string('academic_year_id')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();
        $perPage = (int) $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 20, 50], true)) {
            $perPage = 15;
        }

        $students = Enrollment::query()
            ->with([
                'student.application',
                'section.gradeLevel',
                'gradeLevel',
                'cluster',
                'academicYear',
                'enrollmentStatus',
                'learnerType',
            ])
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->when($academicYearId !== '' && $academicYearId !== 'all', function ($query) use ($academicYearId): void {
                $query->where('SY_ID', $academicYearId);
            })
            ->when($academicYearId === '' && $activeYear, function ($query) use ($activeYear): void {
                $query->where('SY_ID', $activeYear->SY_ID);
            })
            ->when($status !== '' && $status !== 'all', function ($query) use ($status): void {
                $statusId = EnrollmentStatus::idFor($status);

                if ($statusId !== null) {
                    $query->where('enrollment_status_ID', $statusId);
                }
            })
            ->when($learnerType !== '', function ($query) use ($learnerType): void {
                $learnerTypeId = LearnerType::idFor($learnerType);

                if ($learnerTypeId !== null) {
                    $query->where('learner_type_ID', $learnerTypeId);
                }
            })
            ->when($gradeId, function ($query) use ($gradeId): void {
                $query->forGrade($gradeId);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('student', function ($studentQuery) use ($search): void {
                    $studentQuery->where('lrn', 'like', "%{$search}%")
                        ->orWhereHas('application', function ($appQuery) use ($search): void {
                            $appQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($dateFrom !== '' && $dateTo !== '', function ($query) use ($dateFrom, $dateTo): void {
                $query->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);
            })
            ->orderByGrade()
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.registrar.students', [
            'activeYear' => $activeYear,
            'students' => $students,
            'gradeLevels' => GradeLevel::options(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function show(\App\Models\Student $student): \Illuminate\View\View
    {
        $student->load([
            'user',
            'application',
            'profile',
            'guardians',
            'addresses',
            'documents',
            'enrollments' => function ($query) {
                $query->with([
                    'academicYear',
                    'section',
                    'cluster',
                    'grades.assignment.curriculumSubject.subject',
                    'grades.assignment.staff',
                    'enrollmentStatus',
                ])->latest('created_at');
            },
        ]);

        return view('users.registrar.students-show', [
            'student' => $student,
        ]);
    }

    public function studentSf9(Student $student, Enrollment $enrollment): View
    {
        abort_if((int) $enrollment->student_ID !== (int) $student->id, 404);

        $enrollment->load(['student', 'section.academicYear', 'section.cluster', 'section.gradeLevel', 'section.adviser', 'section.curriculum', 'academicYear', 'cluster', 'gradeLevel', 'preferredCourse']);
        $section = $enrollment->section;
        abort_if(! $section, 404);

        $assignments = TeacherSubjectAssignment::query()
            ->with(['curriculumSubject.subject'])
            ->where('section_ID', $section->section_ID)
            ->where('SY_ID', $enrollment->SY_ID)
            ->get();

        $periods = $this->periodsForSection($section);
        $schoolDaysByMonth = Sf9AttendanceSummary::schoolDaysForSection($section);

        $grades = StudentSubjectGrade::query()
            ->whereHas('studentSubject', fn ($query) => $query->where('enrollment_ID', $enrollment->enrollment_ID))
            ->whereIn('assignment_ID', $assignments->pluck('assignment_ID'))
            ->get()
            ->groupBy('assignment_ID')
            ->map(fn ($assignmentGrades) => $assignmentGrades->keyBy('grading_period'));

        $observedValues = StudentObservedValue::query()
            ->where('enrollment_ID', $enrollment->enrollment_ID)
            ->get()
            ->groupBy('statement_key')
            ->map(fn ($statementValues) => $statementValues->keyBy('grading_period'));

        return view('users.teacher.advisory.sf9-print', [
            'section' => $section,
            'periods' => $periods,
            'cards' => collect([
                Sf9ReportCardBuilder::buildCard(
                    $enrollment,
                    $section,
                    $assignments,
                    $grades,
                    $observedValues,
                    $periods,
                    $schoolDaysByMonth,
                ),
            ]),
            'observedValueStatements' => Sf9ReportCardBuilder::observedValueStatements(),
            'observedValueMarkings' => Sf9ReportCardBuilder::observedValueMarkings(),
        ]);
    }

    public function studentSf10(Student $student): View
    {
        $enrollments = Enrollment::query()
            ->with(['student', 'section.academicYear', 'section.adviser', 'section.gradeLevel'])
            ->where('student_ID', $student->id)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->get()
            ->sortBy([
                fn (Enrollment $enrollment): int => (int) preg_replace('/\D+/', '', $enrollment->grade_level),
                fn (Enrollment $enrollment): string => $enrollment->academicYear?->school_year ?? '',
            ])
            ->values();

        abort_if($enrollments->isEmpty(), 404);

        return view('users.teacher.advisory.sf10-print', [
            'section' => $enrollments->last()?->section,
            'periods' => GradingTerm::periodsForSection($enrollments->last()?->section),
            'cards' => LearnerPermanentRecordBuilder::buildCardsForStudents($enrollments->take(1)),
        ]);
    }

    public function gradeApprovals(Request $request): \Illuminate\View\View
    {
        $pendingAssignments = $this->gradeApprovalAssignments('submitted');
        $approvedAssignments = $this->gradeApprovalAssignments('approved');

        return view('users.registrar.grade-approvals', [
            'pendingAssignments' => $pendingAssignments,
            'approvedAssignments' => $approvedAssignments,
        ]);
    }

    private function gradeApprovalAssignments(string $status)
    {
        return TeacherSubjectAssignment::query()
            ->with([
                'section',
                'curriculumSubject.subject',
                'staff',
                'grades' => function ($query) use ($status): void {
                    $query->whereStatus($status);
                },
            ])
            ->whereHas('grades', function ($query) use ($status): void {
                $query->whereStatus($status);
            })
            ->orderBy('section_ID')
            ->get();
    }

    public function showGradeApproval(Request $request, TeacherSubjectAssignment $assignment): View
    {
        $status = $request->string('status')->toString() === 'approved' ? 'approved' : 'submitted';

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

        $submittedPeriodKeys = $assignment->grades
            ->pluck('grading_period')
            ->unique()
            ->values()
            ->all();
        $periods = collect($this->periodsForSection($assignment->section))
            ->filter(fn (array $period): bool => in_array($period['key'], $submittedPeriodKeys, true))
            ->values()
            ->all();
        $gradesByEnrollment = $assignment->grades->groupBy('enrollment_ID');
        $rows = $gradesByEnrollment->map(function ($grades) use ($periods): array {
            $firstGrade = $grades->first();
            $enrollment = $firstGrade?->enrollment;
            $student = $enrollment?->student;
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
                'enrollment' => $enrollment,
                'student' => $student,
                'name' => $application
                    ? trim($application->last_name.', '.$application->first_name.' '.$application->middle_name)
                    : ($student?->name ?? 'N/A'),
                'lrn' => $student?->lrn ?? 'N/A',
                'grades' => $periodGrades,
                'period_values' => $periodValues,
                'average' => count($values) ? round(array_sum($values) / count($values), 2) : null,
                'remarks' => $grades->pluck('remarks')->filter()->unique()->implode(', '),
            ];
        })->sortBy('name')->values();

        return view('users.registrar.grade-approval-show', [
            'assignment' => $assignment,
            'periods' => $periods,
            'rows' => $rows,
            'status' => $status,
            'gradeReturnReasons' => GradeReturnReason::query()->orderBy('name')->get(),
        ]);
    }

    public function approveGrades(Request $request, TeacherSubjectAssignment $assignment): RedirectResponse
    {
        $approved = StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereStatus(GradeStatus::SUBMITTED)
            ->update([
                'grade_status_ID' => GradeStatus::idFor(GradeStatus::APPROVED),
                'reviewed_by' => $request->user()->staff_id,
                'reviewed_at' => now(),
            ]);

        if ($approved > 0) {
            TeacherGradeNotifier::approved($assignment);
        }

        return redirect()
            ->route('registrar.grade-approvals')
            ->with('status', 'Grades approved and sent to the principal for release.');
    }

    public function rejectGrades(Request $request, TeacherSubjectAssignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'grade_return_reason_ID' => ['required', 'integer', 'exists:grade_return_reasons,reason_ID'],
        ], [
            'grade_return_reason_ID.required' => 'Please select a reason for returning these grades.',
            'grade_return_reason_ID.exists' => 'The selected grade return reason is invalid.',
        ]);

        $returned = StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereStatus(GradeStatus::SUBMITTED)
            ->update([
                'grade_status_ID' => GradeStatus::idFor(GradeStatus::REJECTED),
                'reviewed_by' => $request->user()->staff_id,
                'reviewed_at' => now(),
                'grade_return_reason_ID' => $validated['grade_return_reason_ID'],
            ]);

        if (! $returned) {
            return redirect()
                ->route('registrar.grade-approvals')
                ->with('error', 'No submitted grades were available to return.');
        }

        return redirect()
            ->route('registrar.grade-approvals')
            ->with('status', 'Grades returned to teacher for updates.');
    }

    public function classSubjects(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $clusterId = $request->string('cluster_ID')->toString();
        $schoolYearId = $request->string('SY_ID')->toString();
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 20, 50], true)) {
            $perPage = 10;
        }

        $assignmentScope = function ($query): void {
            $query->with(['curriculumSubject.subject', 'staff'])
                ->join('curriculum_subjects', 'teacher_subject_assignments.curr_subj_ID', '=', 'curriculum_subjects.curr_subj_ID')
                ->join('curriculum_grade_levels', 'curriculum_subjects.curriculum_grade_level_ID', '=', 'curriculum_grade_levels.curriculum_ID')
                ->leftJoin('subjects', 'curriculum_subjects.subject_ID', '=', 'subjects.subject_ID')
                ->orderBy('curriculum_grade_levels.semester_ID')
                ->orderBy('subjects.title')
                ->select('teacher_subject_assignments.*');
        };

        $sections = Section::query()
            ->with([
                'gradeLevel',
                'academicYear',
                'cluster',
                'adviser',
                'teacherSubjectAssignments' => $assignmentScope,
            ])
            ->withCount(['enrollments as active_enrollments_count' => function ($subQuery): void {
                $subQuery->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sectionQuery) use ($search): void {
                    $sectionQuery->where('name', 'like', "%{$search}%")
                        ->orWhereHas('teacherSubjectAssignments.curriculumSubject.subject', function ($subjectQuery) use ($search): void {
                            $subjectQuery->where('code', 'like', "%{$search}%")
                                ->orWhere('title', 'like', "%{$search}%");
                        })
                        ->orWhereHas('teacherSubjectAssignments.staff', function ($staffQuery) use ($search): void {
                            $staffQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($gradeId, function ($query) use ($gradeId): void {
                $query->where('grade_ID', $gradeId);
            })
            ->when($clusterId !== '', function ($query) use ($clusterId): void {
                $query->where('cluster_ID', $clusterId);
            })
            ->when($schoolYearId !== '', function ($query) use ($schoolYearId): void {
                $query->where('SY_ID', $schoolYearId);
            })
            ->orderBy('grade_ID')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.registrar.class-subjects.index', [
            'sections' => $sections,
            'clusters' => Cluster::query()->orderBy('name')->get(['cluster_ID', 'name']),
            'academicYears' => AcademicYear::query()->orderByDesc('school_year')->get(['SY_ID', 'school_year']),
            'gradeLevels' => GradeLevel::options(),
            'filters' => [
                'search' => $search,
                'grade_level' => $gradeLevel,
                'cluster_ID' => $clusterId,
                'SY_ID' => $schoolYearId,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function teacherAssignments(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $schoolYearId = $request->string('SY_ID')->toString();
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, [10, 20, 50], true)) {
            $perPage = 10;
        }

        $teachers = Staff::query()
            ->whereHas('role', fn ($query) => $query->where('role_name', 'teacher'))
            ->with([
                'sections' => function ($query) use ($schoolYearId): void {
                    $query->with(['gradeLevel', 'academicYear'])
                        ->when($schoolYearId !== '', fn ($sectionQuery) => $sectionQuery->where('SY_ID', $schoolYearId))
                        ->orderBy('grade_ID')
                        ->orderBy('name');
                },
                'teacherSubjectAssignments' => function ($query) use ($schoolYearId): void {
                    $query->with(['section.gradeLevel', 'section.academicYear', 'curriculumSubject.subject'])
                        ->when($schoolYearId !== '', fn ($assignmentQuery) => $assignmentQuery->where('SY_ID', $schoolYearId))
                        ->orderBy('section_ID');
                },
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($teacherQuery) use ($search): void {
                    $teacherQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_no', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.registrar.teacher-assignments', [
            'teachers' => $teachers,
            'academicYears' => AcademicYear::query()->orderByDesc('school_year')->get(['SY_ID', 'school_year']),
            'filters' => compact('search', 'schoolYearId', 'perPage'),
        ]);
    }

    public function classStudents(Request $request, Section $section): View
    {
        $search = trim($request->string('search')->toString());
        $perPage = (int) $request->input('per_page', 20);

        if (! in_array($perPage, [10, 20, 50], true)) {
            $perPage = 20;
        }

        $section->load(['gradeLevel', 'academicYear', 'adviser']);

        $students = Enrollment::query()
            ->with(['student.application', 'enrollmentStatus'])
            ->where('section_ID', $section->section_ID)
            ->where('SY_ID', $section->SY_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('student', function ($studentQuery) use ($search): void {
                    $studentQuery->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereHas('application', function ($applicationQuery) use ($search): void {
                            $applicationQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('student_ID')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.registrar.class-students', compact('section', 'students', 'search', 'perPage'));
    }

    public function showClassSubject(TeacherSubjectAssignment $assignment): View
    {
        $assignment->load([
            'section.gradeLevel',
            'section.academicYear',
            'section.cluster',
            'section.adviser',
            'curriculumSubject.subject',
            'staff',
            'academicYear',
        ]);

        $periods = GradingTerm::openPeriodsForSection(
            $assignment->section,
            $assignment->curriculumSubject?->semester,
        );
        $termSummaries = collect($periods)
            ->map(fn (array $period): array => AssignmentGradeTermUnlocker::termSummary($assignment, $period))
            ->all();

        return view('users.registrar.class-subjects.show', [
            'assignment' => $assignment,
            'section' => $assignment->section,
            'termSummaries' => $termSummaries,
        ]);
    }

    public function unlockClassSubjectTerm(Request $request, TeacherSubjectAssignment $assignment): RedirectResponse
    {
        $assignment->load(['section.gradeLevel', 'curriculumSubject']);
        $periods = GradingTerm::openPeriodsForSection(
            $assignment->section,
            $assignment->curriculumSubject?->semester,
        );
        $periodKeys = array_column($periods, 'key');

        $validated = $request->validate([
            'grading_period' => ['required', 'string', Rule::in($periodKeys)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = AssignmentGradeTermUnlocker::unlock(
            $assignment,
            $validated['grading_period'],
            $request->user()?->staff_id,
            $validated['notes'] ?? null
        );

        $label = collect($periods)->firstWhere('key', $validated['grading_period'])['label'] ?? $validated['grading_period'];

        return redirect()
            ->route('registrar.class-subjects.show', $assignment)
            ->with('status', "{$label} unlocked for this class subject. {$updated} grade record(s) returned to draft for teacher editing.");
    }

    private function periodsForSection(?Section $section): array
    {
        return GradingTerm::periodsForSection($section);
    }
}
