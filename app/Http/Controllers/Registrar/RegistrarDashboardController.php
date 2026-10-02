<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\DocumentType;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\GradeReturnReason;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\LearnerType;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentObservedValue;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Support\AssignmentGradeTermUnlocker;
use App\Support\GradeRecordPeriodFilters;
use App\Support\LearnerPermanentRecordBuilder;
use App\Support\RegistrarDashboardData;
use App\Support\SectionGradeSubmissionProgress;
use App\Support\Sf9AttendanceSummary;
use App\Support\Sf9ReportCardBuilder;
use App\Support\TeacherGradeNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrarDashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('users.registrar.dashboard', RegistrarDashboardData::forRequest($request));
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
                'student' => fn ($query) => $query->with('application')->withCount([
                    'documents as supporting_documents_count' => fn ($documents) => $documents->where('doc_type', '!=', DocumentType::ID_PHOTO),
                ]),
                'section.gradeLevel',
                'gradeLevel',
                'cluster',
                'academicYear',
                'enrollmentStatus',
                'learnerType',
                'placementStatus',
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

    public function show(Request $request, Student $student): View
    {
        $request->validate(['enrollment_id' => ['nullable', 'integer']]);
        $student->load([
            'user',
            'application',
            'profile',
            'guardians',
            'addresses',
            'documents' => fn ($query) => $query->with(['documentType', 'documentStatus', 'returnReason'])
                ->where('doc_type', '!=', DocumentType::ID_PHOTO)->latest('date_uploaded'),
            'enrollments' => function ($query) {
                $query->with([
                    'academicYear',
                    'section',
                    'cluster',
                    'preferredCourse',
                    'gradeLevel',
                    'learnerType',
                    'placementStatus',
                    'grades.assignment.curriculumSubject.subject',
                    'grades.assignment.staff',
                    'enrollmentStatus',
                ])->latest('created_at');
            },
        ]);

        $enrollment = $request->filled('enrollment_id')
            ? $student->enrollments->firstWhere('enrollment_ID', $request->integer('enrollment_id'))
            : ($student->enrollments->firstWhere('SY_ID', AcademicYear::query()->where('status', true)->value('SY_ID')) ?? $student->enrollments->first());
        abort_if($request->filled('enrollment_id') && ! $enrollment, 404);

        return view('users.registrar.students-show', [
            'student' => $student,
            'enrollment' => $enrollment,
        ]);
    }

    public function viewDocument(Student $student, StudentDocument $document): StreamedResponse|RedirectResponse
    {
        abort_unless((int) $document->student_ID === (int) $student->id, 404);

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            return redirect()->route('registrar.students.show', $student)
                ->withErrors(['document' => 'Document file was not found.']);
        }

        return Storage::disk('public')->response($document->file_path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
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
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $subjectId = $request->integer('subject_id') ?: null;
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $request->validate([
            'term_id' => ['nullable', 'regex:/^(current|[0-9]+)$/'],
            'semester' => ['nullable', 'in:first,second'],
        ]);
        $academicYearId = $request->has('academic_year_id')
            ? ($request->integer('academic_year_id') ?: null)
            : AcademicYear::query()->where('status', true)->value('SY_ID');
        $periods = GradeRecordPeriodFilters::resolve($request, $gradeId ? GradeLevel::find($gradeId) : null);
        $filters = [
            'search' => $search, 'status' => $status, 'subject_id' => $subjectId,
            'grade_level' => $gradeLevel, 'academic_year_id' => $academicYearId,
            'term_id' => $periods['term_id'], 'semester' => $periods['semester'],
        ];

        return view('users.registrar.grade-approvals', [
            'assignments' => $this->gradeApprovalAssignments($status, $subjectId, $gradeId, $academicYearId, $search, $periods['term_ids'], $periods['semester']),
            'subjects' => Subject::query()->with('curriculumSubjects.curriculumGradeLevel')->orderBy('code')->orderBy('title')->get(),
            'filters' => $filters,
            'terms' => $periods['terms'],
            'allTerms' => $periods['allTerms'],
            'termDefaults' => $periods['termDefaults'],
            'activeSemester' => $periods['activeSemester'],
            'showSemesterFilter' => $periods['showSemesterFilter'],
            'gradeLevels' => GradeLevel::query()->orderBy('grade_ID')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['SY_ID', 'school_year']),
        ]);
    }

    private function gradeApprovalAssignments(?string $status = null, ?int $subjectId = null, ?int $gradeId = null, ?int $academicYearId = null, string $search = '', ?array $termIds = null, ?string $semester = null)
    {
        $statuses = [GradeStatus::SUBMITTED, GradeStatus::APPROVED, GradeStatus::RELEASED];
        $status = in_array($status, $statuses, true) ? $status : null;

        return TeacherSubjectAssignment::query()
            ->withoutMapehParents()
            ->with([
                'section.gradeLevel',
                'curriculumSubject.subject',
                'staff',
                'grades' => function ($query) use ($statuses, $status, $termIds): void {
                    $query->whereStatus($status ?: $statuses)->when($termIds !== null, fn ($grades) => $grades->whereIn('term_ID', $termIds))->with('gradeStatus');
                },
            ])
            ->whereHas('grades', function ($query) use ($statuses, $status, $termIds): void {
                $query->whereStatus($status ?: $statuses)->when($termIds !== null, fn ($grades) => $grades->whereIn('term_ID', $termIds));
            })
            ->when($semester, fn ($query) => $query->whereHas('curriculumSubject.curriculumGradeLevel.gradingSemester', fn ($query) => $query->where('key', $semester)))
            ->when($subjectId, function ($query) use ($subjectId): void {
                $query->whereHas('curriculumSubject', fn ($subjectQuery) => $subjectQuery->where('subject_ID', $subjectId));
            })
            ->when($gradeId, function ($query) use ($gradeId): void {
                $query->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('grade_ID', $gradeId));
            })
            ->when($academicYearId, fn ($query) => $query->where('SY_ID', $academicYearId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($assignmentQuery) use ($search): void {
                    $assignmentQuery
                        ->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('curriculumSubject.subject', function ($subjectQuery) use ($search): void {
                            $subjectQuery->where('code', 'like', "%{$search}%")
                                ->orWhere('title', 'like', "%{$search}%");
                        })
                        ->orWhereHas('staff', function ($staffQuery) use ($search): void {
                            $staffQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('section_ID')
            ->get();
    }

    public function showGradeApproval(Request $request, TeacherSubjectAssignment $assignment): View
    {
        $status = $request->string('status')->toString();
        $status = in_array($status, [GradeStatus::APPROVED, GradeStatus::RELEASED], true)
            ? $status : GradeStatus::SUBMITTED;

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

            $average = count($values) ? round(array_sum($values) / count($values), 2) : null;

            return [
                'enrollment' => $enrollment,
                'student' => $student,
                'name' => $application
                    ? trim($application->last_name.', '.$application->first_name.' '.$application->middle_name)
                    : ($student?->name ?? 'N/A'),
                'lrn' => $student?->lrn ?? 'N/A',
                'grades' => $periodGrades,
                'period_values' => $periodValues,
                'average' => $average,
                'remarks' => $average === null ? '' : ($average >= 75 ? 'Passed' : 'Failed'),
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

        $request->attributes->set('audit_description', "Approved {$approved} submitted grade record(s).");

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

        $request->attributes->set('audit_description', "Returned {$returned} submitted grade record(s) to the teacher.");

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
        $periods = $this->classSubjectPeriods($request);
        $clusterId = $periods['showSemesterFilter'] ? $request->string('cluster_ID')->toString() : '';
        $schoolYearId = $request->has('SY_ID') ? $request->string('SY_ID')->toString() : (string) AcademicYear::query()->where('status', true)->value('SY_ID');
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 20, 50], true)) {
            $perPage = 10;
        }

        $sections = Section::query()
            ->with([
                'gradeLevel',
                'academicYear',
                'cluster',
                'adviser',
                'teacherSubjectAssignments' => fn ($query) => $query
                    ->with(['curriculumSubject.subject', 'curriculumSubject.gradingSemester', 'staff'])
                    ->orderBy('assignment_ID'),
            ])
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
            ->when($periods['term_id'] && $periods['term_id'] !== 'current', function ($query) use ($periods): void {
                $term = $periods['allTerms']->firstWhere('term_ID', $periods['term_id']);
                if ($term) {
                    $query->whereHas('gradeLevel', function ($grades) use ($term): void {
                        $term->school_level === 'senior_high'
                            ? $grades->whereIn('grade_label', ['Grade 11', 'Grade 12'])
                            : $grades->whereNotIn('grade_label', ['Grade 11', 'Grade 12']);
                    });
                }
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

        [$assignments, $termsByAssignment, $allTermsByAssignment] = $this->classSubjectSummaries($sections->getCollection(), $periods);

        return view('users.registrar.class-subjects.index', [
            'termsByAssignment' => $termsByAssignment,
            'allTermsByAssignment' => $allTermsByAssignment,
            'sectionProgress' => SectionGradeSubmissionProgress::forAssignments($assignments, $termsByAssignment),
            'sections' => $sections,
            'periods' => $periods,
            'showPeriodColumns' => $sections->getCollection()->contains(fn (Section $section): bool => GradingTerm::isSeniorHighSection($section)),
            'clusters' => Cluster::query()->orderBy('name')->get(['cluster_ID', 'name']),
            'academicYears' => AcademicYear::query()->orderByDesc('school_year')->get(['SY_ID', 'school_year']),
            'gradeLevels' => GradeLevel::options(),
            'filters' => [
                'term_id' => $periods['term_id'],
                'semester' => $periods['semester'],
                'search' => $search,
                'grade_level' => $gradeLevel,
                'cluster_ID' => $clusterId,
                'SY_ID' => $schoolYearId,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function classStatus(Section $section): View
    {
        $section->load([
            'gradeLevel',
            'academicYear',
            'adviser',
            'teacherSubjectAssignments' => function ($query): void {
                $query->with(['curriculumSubject.subject', 'curriculumSubject.gradingSemester', 'staff'])
                    ->withGradeStatusCounts()
                    ->orderBy('assignment_ID');
            },
        ]);

        return view('users.registrar.class-status', compact('section'));
    }

    public function sectionSubjects(Request $request, Section $section): View
    {
        $section->load([
            'gradeLevel',
            'teacherSubjectAssignments' => fn ($query) => $query
                ->with(['curriculumSubject.subject', 'curriculumSubject.gradingSemester', 'staff'])
                ->orderBy('assignment_ID'),
        ]);

        $periods = $this->classSubjectPeriods($request);
        [, $termsByAssignment, $allTermsByAssignment] = $this->classSubjectSummaries(collect([$section]), $periods);

        $sectionProgress = SectionGradeSubmissionProgress::forAssignments($section->teacherSubjectAssignments, $termsByAssignment);

        return view('users.registrar.class-subjects.subjects-modal', compact('section', 'termsByAssignment', 'allTermsByAssignment', 'sectionProgress'));
    }

    public function classSubjectGradeRecords(Request $request, TeacherSubjectAssignment $assignment): View
    {
        $assignment->load(['section.gradeLevel', 'curriculumSubject.gradingSemester']);
        $periods = GradingTerm::periodsForSection($assignment->section, $assignment->curriculumSubject?->semester);
        $validated = $request->validate([
            'grading_period' => ['required', Rule::in(array_column($periods, 'key'))],
        ]);
        $period = collect($periods)->firstWhere('key', $validated['grading_period']);
        $grades = $assignment->grades()
            ->forPeriodKey($period['key'])
            ->whereStatus(GradeStatus::teacherLockedSlugs())
            ->whereHas('studentSubject', function ($query) use ($assignment): void {
                $query->where('curr_subj_ID', $assignment->curr_subj_ID)
                    ->whereHas('enrollment', function ($enrollments) use ($assignment): void {
                        $enrollments->where('section_ID', $assignment->section_ID)
                            ->where('SY_ID', $assignment->SY_ID)
                            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
                    });
            })
            ->with(['studentSubject.enrollment.student', 'gradeStatus'])
            ->get()->sortBy(fn ($grade) => $grade->studentSubject?->enrollment?->student?->last_name);

        return view('users.registrar.class-subjects.grade-records', compact('grades', 'period'));
    }

    private function classSubjectPeriods(Request $request): array
    {
        $request->validate([
            'term_id' => ['nullable', 'regex:/^(current|[0-9]+)$/'],
            'semester' => ['nullable', 'in:first,second'],
        ]);
        $gradeId = GradeLevel::idForValue($request->string('grade_level')->toString());
        $periods = GradeRecordPeriodFilters::resolve($request, $gradeId ? GradeLevel::find($gradeId) : null);
        // Mixed-level results still use the active semester for their SHS subjects.
        if (! $gradeId) {
            $periods['semester'] = $request->has('semester') ? $request->input('semester') : $periods['activeSemester'];
        }

        return $periods;
    }

    private function classSubjectSummaries($sections, array $periods): array
    {
        $assignments = $sections->flatMap(function (Section $section) use ($periods) {
            $subjects = $section->teacherSubjectAssignments->filter(function ($assignment) use ($section, $periods): bool {
                return ! GradingTerm::isSeniorHighSection($section)
                    || ! $periods['semester']
                    || ! $assignment->curriculumSubject?->semester
                    || $assignment->curriculumSubject->semester === $periods['semester'];
            })->values();
            $section->setRelation('teacherSubjectAssignments', $subjects);
            foreach ($subjects as $assignment) {
                $assignment->setRelation('section', $section);
            }

            return $subjects;
        });
        $summaries = AssignmentGradeTermUnlocker::termSummariesForAssignments($assignments, true);
        $allTermsByAssignment = $summaries;
        foreach ($assignments as $assignment) {
            $seniorHigh = GradingTerm::isSeniorHighSection($assignment->section);
            $activeTermId = $periods['termDefaults'][$seniorHigh ? 'senior_high' : 'junior_high'] ?? null;
            $activePrefix = $periods['activeSemester'] === 'second' ? 'shs_sem2_' : 'shs_sem1_';
            $assignment->active_term_summary = collect($summaries[$assignment->assignment_ID] ?? [])->first(
                fn (array $term): bool => (int) $term['term_ID'] === (int) $activeTermId
                    && (! $seniorHigh || str_starts_with($term['key'], $activePrefix))
            );
            $terms = array_values(array_filter($summaries[$assignment->assignment_ID] ?? [], function (array $term) use ($assignment, $periods): bool {
                if ($periods['term_ids'] !== null && ! in_array((int) $term['term_ID'], $periods['term_ids'], true)) {
                    return false;
                }
                if (GradingTerm::isSeniorHighSection($assignment->section) && $periods['semester']) {
                    $prefix = $periods['semester'] === 'second' ? 'shs_sem2_' : 'shs_sem1_';

                    return str_starts_with($term['key'], $prefix);
                }

                return true;
            }));
            $summaries[$assignment->assignment_ID] = $terms;
            $assignment->grades_count = array_sum(array_column($terms, 'total'));
            foreach (GradeStatus::slugs() as $status) {
                $assignment->{$status.'_grades_count'} = array_sum(array_column($terms, $status));
            }
        }

        return [$assignments, $summaries, $allTermsByAssignment];
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

    public function unlockClassSubjectTerm(Request $request, TeacherSubjectAssignment $assignment): RedirectResponse|JsonResponse
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$label} unlocked for this class subject. {$updated} grade record(s) returned to draft for teacher editing.",
            ]);
        }

        return redirect()
            ->route('registrar.class-subjects.show', $assignment)
            ->with('status', "{$label} unlocked for this class subject. {$updated} grade record(s) returned to draft for teacher editing.");
    }

    private function periodsForSection(?Section $section): array
    {
        return GradingTerm::periodsForSection($section);
    }
}
