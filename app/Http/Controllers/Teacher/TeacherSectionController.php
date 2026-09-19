<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherSectionGradesRequest;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Enrollment;
use App\Models\EnrollmentMonthlyAttendance;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\Section;
use App\Models\SectionAttendanceSetting;
use App\Models\SectionSf2Upload;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\StudentGuardian;
use App\Models\StudentObservedValue;
use App\Models\StudentProfile;
use App\Models\StudentSubjectGrade;
use App\Models\StudentSubject;
use App\Models\TeacherSubjectAssignment;
use App\Support\AssignmentGradeTermUnlocker;
use App\Support\ClassListSpreadsheet;
use App\Support\LearnerPermanentRecordBuilder;
use App\Support\PromotionEligibility;
use App\Support\Sf9AttendanceSummary;
use App\Support\Sf9ReportCardBuilder;
use App\Support\StudentCredentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherSectionController extends Controller
{
    public function index(Request $request): View
    {
        $staff = $request->user();
        $sections = collect();
        $search = $request->string('search')->toString();
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $clusterId = $request->string('cluster_ID')->toString();
        $schoolYearId = $request->string('SY_ID')->toString();
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 20, 50], true)) {
            $perPage = 10;
        }

        if ($staff) {
            $assignmentScope = function ($query) use ($staff): void {
                $query->with(['curriculumSubject.subject'])
                    ->where('staff_ID', $staff->staff_id)
                    ->join('curriculum_subjects', 'teacher_subject_assignments.curr_subj_ID', '=', 'curriculum_subjects.curr_subj_ID')
                    ->join('curriculum_grade_levels', 'curriculum_subjects.curriculum_grade_level_ID', '=', 'curriculum_grade_levels.curriculum_ID')
                    ->leftJoin('subjects', 'curriculum_subjects.subject_ID', '=', 'subjects.subject_ID')
                    ->orderBy('curriculum_grade_levels.semester_ID')
                    ->orderBy('subjects.title')
                    ->select('teacher_subject_assignments.*');
            };

            $sections = Section::query()
                ->with([
                    'academicYear',
                    'cluster',
                    'gradeLevel',
                    'teacherSubjectAssignments' => $assignmentScope,
                ])
                ->withCount(['enrollments as active_enrollments_count' => function ($subQuery) {
                    $subQuery->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
                }])
                ->whereHas('teacherSubjectAssignments', function ($query) use ($staff, $search): void {
                    $query->where('staff_ID', $staff->staff_id)
                        ->when($search !== '', function ($assignmentQuery) use ($search): void {
                            $assignmentQuery->where(function ($inner) use ($search): void {
                                $inner->whereHas('section', function ($sectionQuery) use ($search): void {
                                    $sectionQuery->where('name', 'like', '%'.$search.'%');
                                })->orWhereHas('curriculumSubject.subject', function ($subjectQuery) use ($search): void {
                                    $subjectQuery->where('title', 'like', '%'.$search.'%')
                                        ->orWhere('code', 'like', '%'.$search.'%');
                                });
                            });
                        });
                })
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->where('name', 'like', '%'.$search.'%')
                            ->orWhereHas('teacherSubjectAssignments.curriculumSubject.subject', function ($subjectQuery) use ($search): void {
                                $subjectQuery->where('title', 'like', '%'.$search.'%')
                                    ->orWhere('code', 'like', '%'.$search.'%');
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
        }

        return view('users.teacher.sections.index', [
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

    public function advisoryIndex(Request $request): View
    {
        $staffId = $request->user()?->staff_id;

        $sections = Section::query()
            ->with(['academicYear', 'cluster', 'gradeLevel'])
            ->withCount(['enrollments as active_enrollments_count' => function ($query): void {
                $query->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->where('staff_ID', $staffId)
            ->orderByDesc('SY_ID')
            ->orderBy('grade_ID')
            ->orderBy('name')
            ->get();

        return view('users.teacher.advisory.index', [
            'sections' => $sections,
        ]);
    }

    public function advisoryShow(Request $request, Section $section): View
    {
        $this->authorizeAdvisorySection($request, $section);

        $section->load(['academicYear', 'cluster', 'gradeLevel']);
        $periods = GradingTerm::openPeriodsForSection($section);
        $search = trim($request->string('search')->toString());
        $selectedAssignmentId = (int) $request->integer('assignment_id');

        $enrollments = Enrollment::query()
            ->with(['student.application', 'promotionStatus'])
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->orderBy('enrollment_status_ID')
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $this->studentSortKey($enrollment))
            ->filter(function (Enrollment $enrollment) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                $student = $enrollment->student;
                $application = $student?->application;
                $searchable = implode(' ', array_filter([
                    $student?->lrn,
                    $student?->first_name,
                    $student?->middle_name,
                    $student?->last_name,
                    $application?->first_name,
                    $application?->middle_name,
                    $application?->last_name,
                ]));

                return str_contains(mb_strtolower($searchable), mb_strtolower($search));
            })
            ->values();

        $assignments = TeacherSubjectAssignment::query()
            ->with(['curriculumSubject.subject', 'staff'])
            ->where('section_ID', $section->section_ID)
            ->where('SY_ID', $section->SY_ID)
            ->orderBy('curr_subj_ID')
            ->get();
        $availableAssignments = $assignments;

        if ($selectedAssignmentId && ! $assignments->contains('assignment_ID', $selectedAssignmentId)) {
            $selectedAssignmentId = 0;
        }
        if ($selectedAssignmentId) {
            $assignments = $assignments->where('assignment_ID', $selectedAssignmentId)->values();
        }

        $grades = StudentSubjectGrade::query()
            ->with('studentSubject')
            ->whereHas('studentSubject', fn ($query) => $query->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID')))
            ->whereIn('assignment_ID', $assignments->pluck('assignment_ID'))
            ->get()
            ->groupBy(fn (StudentSubjectGrade $grade) => $grade->studentSubject?->enrollment_ID)
            ->map(fn ($studentGrades) => $studentGrades
                ->groupBy('assignment_ID')
                ->map(fn ($assignmentGrades) => $assignmentGrades->keyBy('grading_period')));

        $rows = $enrollments->map(function (Enrollment $enrollment) use ($assignments, $grades, $periods): array {
            $student = $enrollment->student;
            $application = $student?->application;
            $studentGrades = $grades->get($enrollment->enrollment_ID, collect());
            $subjects = [];
            $overallValues = [];

            foreach ($assignments as $assignment) {
                $periodGrades = $studentGrades->get($assignment->assignment_ID, collect());
                $values = [];

                foreach ($periods as $period) {
                    $value = $periodGrades->get($period['key'])?->numeric_grade;
                    if ($value !== null) {
                        $values[] = (float) $value;
                        $overallValues[] = (float) $value;
                    }
                }

                $subjects[$assignment->assignment_ID] = [
                    'periods' => $periodGrades,
                    'average' => count($values) ? round(array_sum($values) / count($values), 2) : null,
                ];
            }

            return [
                'enrollment' => $enrollment,
                'student' => $student,
                'name' => $application
                    ? trim($application->last_name.', '.$application->first_name.' '.$application->middle_name)
                    : ($student?->name ?? 'N/A'),
                'lrn' => $student?->lrn ?? 'N/A',
                'subjects' => $subjects,
                'overall_average' => count($overallValues) ? round(array_sum($overallValues) / count($overallValues), 2) : null,
            ];
        });

        return view('users.teacher.advisory.show', [
            'section' => $section,
            'periods' => $periods,
            'assignments' => $assignments,
            'availableAssignments' => $availableAssignments,
            'rows' => $rows,
            'selectedAssignmentId' => $selectedAssignmentId,
        ]);
    }

    public function advisoryClassList(Request $request, Section $section): View
    {
        $this->authorizeAdvisorySection($request, $section);

        $section->load(['academicYear', 'cluster', 'gradeLevel']);
        $search = trim($request->string('search')->toString());
        $sex = $request->string('sex')->toString();
        if (! in_array($sex, ['', 'male', 'female', 'unspecified'], true)) {
            $sex = '';
        }

        $enrollments = Enrollment::query()
            ->with(['student.application'])
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->get()
            ->filter(function (Enrollment $enrollment) use ($search, $sex): bool {
                $student = $enrollment->student;
                $application = $student?->application;
                $studentSex = strtolower((string) $student?->sex);

                if ($sex === 'unspecified') {
                    if (in_array($studentSex, ['male', 'female'], true)) {
                        return false;
                    }
                } elseif ($sex !== '' && $studentSex !== $sex) {
                    return false;
                }

                if ($search === '') {
                    return true;
                }

                $searchable = implode(' ', array_filter([
                    $student?->lrn,
                    $student?->first_name,
                    $student?->middle_name,
                    $student?->last_name,
                    $application?->first_name,
                    $application?->middle_name,
                    $application?->last_name,
                ]));

                return str_contains(mb_strtolower($searchable), mb_strtolower($search));
            })
            ->sortBy(fn (Enrollment $enrollment) => $this->studentSortKey($enrollment))
            ->values();

        return view('users.teacher.advisory.class-list', compact('section', 'enrollments', 'search', 'sex'));
    }

    public function advisoryStudentProfile(Request $request, Section $section, Enrollment $enrollment): View
    {
        $this->authorizeAdvisorySection($request, $section);

        abort_unless($enrollment->section_ID === $section->section_ID, 404);

        $section->load(['academicYear', 'cluster', 'gradeLevel']);
        $enrollment->load([
            'student.profile',
            'student.guardians',
            'student.addresses',
        ]);

        return view('users.teacher.advisory.student-profile', [
            'section' => $section,
            'student' => $enrollment->student,
            'backUrl' => route('teacher.advisory.class-list.index', [
                'section' => $section,
                'search' => $request->string('search')->toString(),
                'sex' => $request->string('sex')->toString(),
            ]),
        ]);
    }

    public function evaluatePromotions(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeAdvisorySection($request, $section);

        $validated = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer'],
        ]);
        $enrollments = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->whereIn('enrollment_ID', $validated['enrollment_ids'])
            ->get();
        $counts = ['eligible' => 0, 'retained' => 0, 'pending' => 0];

        DB::transaction(function () use ($enrollments, &$counts): void {
            foreach ($enrollments as $enrollment) {
                $result = PromotionEligibility::evaluate($enrollment);
                $enrollment->update(['promotion_status' => $result['status']]);
                $counts[$result['status']]++;
            }
        });

        return back()->with('status', "Promotion evaluation completed: {$counts['eligible']} eligible, {$counts['retained']} retained, {$counts['pending']} pending final grades.");
    }

    public function advisoryPromotions(Request $request, Section $section): View
    {
        $this->authorizeAdvisorySection($request, $section);
        $section->load(['academicYear', 'cluster', 'gradeLevel']);

        $enrollments = Enrollment::query()
            ->with(['student.application', 'promotionStatus'])
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $this->studentSortKey($enrollment))
            ->values();

        return view('users.teacher.advisory.promotions', compact('section', 'enrollments'));
    }

    public function advisoryAttendance(Request $request, Section $section): View
    {
        $this->authorizeAdvisorySection($request, $section);

        $section->load(['academicYear', 'cluster', 'gradeLevel']);
        $months = Sf9AttendanceSummary::months();
        $schoolDays = Sf9AttendanceSummary::schoolDaysForSection($section);

        $enrollments = Enrollment::query()
            ->with(['student'])
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->join('students', 'enrollments.student_ID', '=', 'students.id')
            ->orderByRaw("CASE LOWER(COALESCE(students.sex, '')) WHEN 'male' THEN 0 WHEN 'female' THEN 1 ELSE 2 END")
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->select('enrollments.*')
            ->get();

        $attendanceRecords = EnrollmentMonthlyAttendance::query()
            ->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID'))
            ->get()
            ->groupBy('enrollment_ID')
            ->map(fn ($records) => $records->keyBy('month'));

        $rows = $enrollments->map(function (Enrollment $enrollment) use ($attendanceRecords, $schoolDays): array {
            $student = $enrollment->student;
            $records = $attendanceRecords->get($enrollment->enrollment_ID, collect());
            $summary = Sf9AttendanceSummary::forEnrollment($enrollment->enrollment_ID, $schoolDays);

            return [
                'enrollment' => $enrollment,
                'name' => $student ? trim($student->last_name.', '.$student->first_name.' '.$student->middle_name) : 'N/A',
                'lrn' => $student?->lrn ?? 'N/A',
                'records' => $records,
                'summary' => $summary,
            ];
        });

        $sf2Uploads = SectionSf2Upload::query()
            ->where('section_ID', $section->section_ID)
            ->where('SY_ID', $section->SY_ID)
            ->latest()
            ->limit(10)
            ->get();

        return view('users.teacher.advisory.attendance', [
            'section' => $section,
            'months' => $months,
            'schoolDays' => $schoolDays,
            'rows' => $rows,
            'sf2Uploads' => $sf2Uploads,
        ]);
    }

    public function storeAdvisoryAttendance(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeAdvisorySection($request, $section);

        $validated = $request->validate([
            'school_days' => ['nullable', 'array'],
            'school_days.*' => ['nullable', 'integer', 'min:0', 'max:31'],
            'attendance' => ['nullable', 'array'],
            'attendance.*' => ['nullable', 'array'],
            'attendance.*.*' => ['nullable', 'array'],
            'attendance.*.*.present' => ['nullable', 'integer', 'min:0', 'max:31'],
            'attendance.*.*.absent' => ['nullable', 'integer', 'min:0', 'max:31'],
        ]);

        $allowedEnrollmentIds = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->pluck('enrollment_ID')
            ->map(fn ($id) => (int) $id)
            ->all();
        $allowedEnrollmentIds = array_flip($allowedEnrollmentIds);
        $monthKeys = array_flip(Sf9AttendanceSummary::monthKeys());

        DB::transaction(function () use ($validated, $section, $allowedEnrollmentIds, $monthKeys): void {
            foreach (($validated['school_days'] ?? []) as $month => $schoolDays) {
                if (! isset($monthKeys[(int) $month])) {
                    continue;
                }

                SectionAttendanceSetting::query()->updateOrCreate(
                    [
                        'section_ID' => $section->section_ID,
                        'SY_ID' => $section->SY_ID,
                        'month' => (int) $month,
                    ],
                    ['school_days' => (int) ($schoolDays ?? 0)]
                );
            }

            foreach (($validated['attendance'] ?? []) as $enrollmentId => $months) {
                if (! isset($allowedEnrollmentIds[(int) $enrollmentId]) || ! is_array($months)) {
                    continue;
                }

                foreach ($months as $month => $values) {
                    if (! isset($monthKeys[(int) $month]) || ! is_array($values)) {
                        continue;
                    }

                    $present = (int) ($values['present'] ?? 0);
                    $absent = (int) ($values['absent'] ?? 0);

                    if ($present === 0 && $absent === 0) {
                        EnrollmentMonthlyAttendance::query()
                            ->where('enrollment_ID', (int) $enrollmentId)
                            ->where('month', (int) $month)
                            ->delete();

                        continue;
                    }

                    EnrollmentMonthlyAttendance::query()->updateOrCreate(
                        [
                            'enrollment_ID' => (int) $enrollmentId,
                            'month' => (int) $month,
                        ],
                        [
                            'days_present' => $present,
                            'days_absent' => $absent,
                        ]
                    );
                }
            }
        });

        return back()->with('status', 'Attendance records saved successfully.');
    }

    public function uploadAdvisorySf2(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeAdvisorySection($request, $section);

        $validated = $request->validate([
            'report_month' => ['required', 'integer', 'in:'.implode(',', Sf9AttendanceSummary::monthKeys())],
            'sf2_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $file = $validated['sf2_file'];
        $path = $file->storeAs(
            'sf2-uploads/'.$section->section_ID,
            Str::uuid().'.pdf',
            'local'
        );

        SectionSf2Upload::query()->create([
            'section_ID' => $section->section_ID,
            'SY_ID' => $section->SY_ID,
            'report_month' => (int) $validated['report_month'],
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'status' => 'pending',
            'parse_notes' => 'Queued for SF2 import. Automatic counting will be enabled in a future update.',
            'uploaded_by' => $request->user()?->staff_id,
        ]);

        return back()->with('status', 'SF2 file uploaded successfully. Attendance import from SF2 will be available soon.');
    }

    public function advisorySf9(Request $request, Section $section): View
    {
        $this->authorizeAdvisorySection($request, $section);

        $section->load(['academicYear', 'cluster', 'gradeLevel', 'adviser', 'curriculum']);
        $selectedEnrollmentIds = collect((array) $request->input('enrollment_ids', []))
            ->push($request->input('enrollment_ID'))
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $enrollments = Enrollment::query()
            ->with(['student', 'cluster', 'preferredCourse', 'academicYear'])
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->when($selectedEnrollmentIds->isNotEmpty(), function ($query) use ($selectedEnrollmentIds): void {
                $query->whereIn('enrollment_ID', $selectedEnrollmentIds);
            })
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $this->studentSortKey($enrollment))
            ->values();

        if ($enrollments->isEmpty()) {
            abort(404);
        }

        $assignments = TeacherSubjectAssignment::query()
            ->with(['curriculumSubject.subject'])
            ->where('section_ID', $section->section_ID)
            ->where('SY_ID', $section->SY_ID)
            ->get();
        $periods = $this->periodsForSection($section);
        $schoolDaysByMonth = Sf9AttendanceSummary::schoolDaysForSection($section);

        $grades = StudentSubjectGrade::query()
            ->with('studentSubject')
            ->whereHas('studentSubject', fn ($query) => $query->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID')))
            ->whereIn('assignment_ID', $assignments->pluck('assignment_ID'))
            ->get()
            ->groupBy(fn (StudentSubjectGrade $grade) => $grade->studentSubject?->enrollment_ID)
            ->map(fn ($studentGrades) => $studentGrades
                ->groupBy('assignment_ID')
                ->map(fn ($assignmentGrades) => $assignmentGrades->keyBy('grading_period')));

        $observedValues = StudentObservedValue::query()
            ->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID'))
            ->get()
            ->groupBy('enrollment_ID')
            ->map(fn ($studentValues) => $studentValues
                ->groupBy('statement_key')
                ->map(fn ($statementValues) => $statementValues->keyBy('grading_period')));

        $principalName = Sf9ReportCardBuilder::principalName();
        $cards = $enrollments->map(fn (Enrollment $enrollment): array => Sf9ReportCardBuilder::buildCard(
            $enrollment,
            $section,
            $assignments,
            $grades->get($enrollment->enrollment_ID, collect()),
            $observedValues->get($enrollment->enrollment_ID, collect()),
            $periods,
            $schoolDaysByMonth,
            $principalName,
        ));

        return view('users.teacher.advisory.sf9-print', [
            'section' => $section,
            'periods' => $periods,
            'cards' => $cards,
            'observedValueStatements' => Sf9ReportCardBuilder::observedValueStatements(),
            'observedValueMarkings' => Sf9ReportCardBuilder::observedValueMarkings(),
        ]);
    }

    public function advisorySf10(Request $request, Section $section): View
    {
        $this->authorizeAdvisorySection($request, $section);

        $section->load(['academicYear', 'cluster', 'gradeLevel', 'adviser']);
        $selectedEnrollmentIds = collect((array) $request->input('enrollment_ids', []))
            ->push($request->input('enrollment_ID'))
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $enrollments = Enrollment::query()
            ->with(['student'])
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->when($selectedEnrollmentIds->isNotEmpty(), function ($query) use ($selectedEnrollmentIds): void {
                $query->whereIn('enrollment_ID', $selectedEnrollmentIds);
            })
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $this->studentSortKey($enrollment))
            ->values();

        if ($enrollments->isEmpty()) {
            abort(404);
        }

        return view('users.teacher.advisory.sf10-print', [
            'section' => $section,
            'periods' => GradingTerm::periodsForSection($section),
            'cards' => LearnerPermanentRecordBuilder::buildCardsForStudents($enrollments),
        ]);
    }

    public function advisoryObservedValues(Request $request, Section $section): View
    {
        $this->authorizeAdvisorySection($request, $section);

        $section->load(['academicYear', 'cluster', 'gradeLevel']);
        $statements = Sf9ReportCardBuilder::observedValueStatements();
        $coreValues = array_values(array_unique(array_column($statements, 'core_value')));
        $activeCoreValue = $request->string('core')->toString();
        if (! in_array($activeCoreValue, $coreValues, true)) {
            $activeCoreValue = $coreValues[0] ?? '';
        }

        $enrollments = Enrollment::query()
            ->with(['student'])
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->join('students', 'enrollments.student_ID', '=', 'students.id')
            ->orderByRaw("CASE LOWER(COALESCE(students.sex, '')) WHEN 'male' THEN 0 WHEN 'female' THEN 1 ELSE 2 END")
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->orderBy('students.lrn')
            ->select('enrollments.*')
            ->get();

        $observedValues = StudentObservedValue::query()
            ->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID'))
            ->get()
            ->groupBy('enrollment_ID')
            ->map(fn ($studentValues) => $studentValues
                ->groupBy('statement_key')
                ->map(fn ($statementValues) => $statementValues->keyBy('grading_period')));

        $allEnrollmentIds = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->pluck('enrollment_ID');
        $isObservedLocked = StudentObservedValue::query()
            ->whereIn('enrollment_ID', $allEnrollmentIds)
            ->where('status', 'submitted')
            ->exists();
        $isGradingInputOpen = $this->isGradingInputOpen($section);
        $periods = $this->gradingInputPeriods($section);
        $lockedPeriodKeys = GradingTerm::lockedPeriodKeysForSection($section);
        $editablePeriodKey = GradingTerm::currentEditablePeriodKeyForSection($section);
        $editablePeriodLabel = GradingTerm::currentEditablePeriodLabelForSection($section);

        return view('users.teacher.advisory.observed-values', [
            'section' => $section,
            'enrollments' => $enrollments,
            'observedValues' => $observedValues,
            'statements' => $statements,
            'activeCoreValue' => $activeCoreValue,
            'periods' => $periods,
            'markings' => Sf9ReportCardBuilder::observedValueMarkings(),
            'isObservedLocked' => $isObservedLocked || ! $isGradingInputOpen,
            'gradingInputClosed' => ! $isGradingInputOpen,
            'lockedPeriodKeys' => $lockedPeriodKeys,
            'editablePeriodKey' => $editablePeriodKey,
            'editablePeriodLabel' => $editablePeriodLabel,
        ]);
    }

    public function storeAdvisoryObservedValues(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeAdvisorySection($request, $section);

        $validated = $request->validate([
            'markings' => ['nullable', 'array'],
        ]);

        $allowedEnrollmentIds = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->pluck('enrollment_ID')
            ->map(fn ($id) => (int) $id)
            ->all();
        $allowedEnrollmentIds = array_flip($allowedEnrollmentIds);
        $statementKeys = array_flip(array_column(Sf9ReportCardBuilder::observedValueStatements(), 'key'));
        $periods = $this->gradingInputPeriods($section);
        $periodKeys = array_flip(array_column($periods, 'key'));
        $lockedPeriodKeys = array_flip(GradingTerm::lockedPeriodKeysForSection($section));
        $editablePeriodKey = GradingTerm::currentEditablePeriodKeyForSection($section);
        $markingKeys = array_flip(array_keys(Sf9ReportCardBuilder::observedValueMarkings()));
        $staffId = $request->user()->staff_id;
        $saved = 0;
        $removed = 0;

        $hasSubmittedValues = StudentObservedValue::query()
            ->whereIn('enrollment_ID', array_keys($allowedEnrollmentIds))
            ->where('status', 'submitted')
            ->exists();

        if ($hasSubmittedValues) {
            return back()->withErrors(['markings' => 'Submitted observed values are locked and can no longer be edited.']);
        }

        if (! $this->isGradingInputOpen($section)) {
            return back()->withErrors(['markings' => 'This grading term is closed for teacher input.']);
        }

        if ($editablePeriodKey === null) {
            return back()->withErrors(['markings' => 'No grading term is currently open for input.']);
        }

        DB::transaction(function () use ($validated, $allowedEnrollmentIds, $statementKeys, $periodKeys, $lockedPeriodKeys, $editablePeriodKey, $markingKeys, $staffId, &$saved, &$removed): void {
            foreach (($validated['markings'] ?? []) as $enrollmentId => $statements) {
                if (! isset($allowedEnrollmentIds[(int) $enrollmentId]) || ! is_array($statements)) {
                    continue;
                }

                foreach ($statements as $statementKey => $periods) {
                    if (! isset($statementKeys[$statementKey]) || ! is_array($periods)) {
                        continue;
                    }

                    foreach ($periods as $periodKey => $marking) {
                        if (isset($lockedPeriodKeys[$periodKey]) || $periodKey !== $editablePeriodKey) {
                            continue;
                        }

                        if (! is_string($marking) || ! isset($periodKeys[$periodKey])) {
                            continue;
                        }

                        if ($marking === '') {
                            $removed += StudentObservedValue::query()
                                ->where('enrollment_ID', (int) $enrollmentId)
                                ->where('statement_key', $statementKey)
                                ->where('grading_period', $periodKey)
                                ->delete();

                            continue;
                        }

                        if (! isset($markingKeys[$marking])) {
                            continue;
                        }

                        StudentObservedValue::query()->updateOrCreate(
                            [
                                'enrollment_ID' => (int) $enrollmentId,
                                'statement_key' => $statementKey,
                                'grading_period' => $periodKey,
                            ],
                            [
                                'marking' => $marking,
                                'status' => 'draft',
                                'submitted_at' => null,
                                'reviewed_by' => null,
                                'reviewed_at' => null,
                                'posted_by' => $staffId,
                            ]
                        );
                        $saved++;
                    }
                }
            }
        });

        $parameters = ['section' => $section];
        $activeCoreValue = $request->string('active_core')->toString();
        if (in_array($activeCoreValue, array_column(Sf9ReportCardBuilder::observedValueStatements(), 'core_value'), true)) {
            $parameters['core'] = $activeCoreValue;
        }

        return redirect()->route('teacher.advisory.observed-values', $parameters)
            ->with('status', "{$saved} observed value marking(s) saved. {$removed} marking(s) cleared.");
    }

    public function bulkStoreAdvisoryObservedValues(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeAdvisorySection($request, $section);

        $validated = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['required', 'integer'],
            'statement_key' => ['required', 'string'],
            'grading_period' => ['required', 'string'],
            'marking' => ['required', 'string'],
        ]);

        $statementKeys = array_flip(array_column(Sf9ReportCardBuilder::observedValueStatements(), 'key'));
        $periodKeys = array_flip(array_column($this->gradingInputPeriods($section), 'key'));
        $editablePeriodKey = GradingTerm::currentEditablePeriodKeyForSection($section);
        $markingKeys = array_flip(array_keys(Sf9ReportCardBuilder::observedValueMarkings()));

        if (! isset($statementKeys[$validated['statement_key']], $periodKeys[$validated['grading_period']], $markingKeys[$validated['marking']])) {
            return back()->withErrors(['marking' => 'Please select a valid statement, term, and marking.']);
        }

        if ($validated['grading_period'] !== $editablePeriodKey) {
            return back()->withErrors(['marking' => 'Only the current school grading term can be edited.']);
        }

        if (! $this->isGradingInputOpen($section)) {
            return back()->withErrors(['marking' => 'This grading term is closed for teacher input.']);
        }

        $enrollmentIds = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->whereIn('enrollment_ID', $validated['enrollment_ids'])
            ->pluck('enrollment_ID');

        $staffId = $request->user()->staff_id;

        $hasSubmittedValues = StudentObservedValue::query()
            ->whereIn('enrollment_ID', $enrollmentIds)
            ->where('status', 'submitted')
            ->exists();

        if ($hasSubmittedValues) {
            return back()->withErrors(['marking' => 'Submitted observed values are locked and can no longer be edited.']);
        }

        DB::transaction(function () use ($enrollmentIds, $validated, $staffId): void {
            foreach ($enrollmentIds as $enrollmentId) {
                StudentObservedValue::query()->updateOrCreate(
                    [
                        'enrollment_ID' => $enrollmentId,
                        'statement_key' => $validated['statement_key'],
                        'grading_period' => $validated['grading_period'],
                    ],
                    [
                        'marking' => $validated['marking'],
                        'status' => 'draft',
                        'submitted_at' => null,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'posted_by' => $staffId,
                    ]
                );
            }
        });

        return back()->with('status', "{$enrollmentIds->count()} learner marking(s) updated.");
    }

    public function submitAdvisoryObservedValues(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeAdvisorySection($request, $section);

        if (! $this->isGradingInputOpen($section)) {
            return back()->withErrors(['markings' => 'This grading term is closed for teacher input.']);
        }

        $enrollmentIds = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->pluck('enrollment_ID');

        $updated = StudentObservedValue::query()
            ->whereIn('enrollment_ID', $enrollmentIds)
            ->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);

        if (! $updated) {
            return back()->withErrors(['markings' => 'No observed values available to submit.'])->withInput();
        }

        return back()->with('status', 'Observed values submitted and locked.');
    }

    public function show(Request $request, TeacherSubjectAssignment $assignment): View
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load(['section.academicYear', 'section.cluster', 'section.gradeLevel', 'curriculumSubject.subject']);
        $section = $assignment->section;
        $semester = $assignment->curriculumSubject?->semester;
        // A section's roster spans both Senior High semesters. The active
        // semester chooses the subject and grading period, not its students.
        $enrollments = Enrollment::query()
            ->with(['student'])
            ->where('section_ID', $section->section_ID)
            ->orderBy('enrollment_status_ID')
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $this->studentSortKey($enrollment))
            ->values();

        $periods = GradingTerm::periodsForSection($section, $semester);
        $inputPeriods = $this->gradingInputPeriods($section, $semester);
        $periodKeys = array_column($inputPeriods, 'key');
        $editablePeriodKeys = AssignmentGradeTermUnlocker::editablePeriodKeysFor($assignment->assignment_ID);
        $editablePeriodKeys = array_values(array_intersect($editablePeriodKeys, $periodKeys));
        $lockedPeriodKeys = AssignmentGradeTermUnlocker::lockedPeriodKeysForAssignment($assignment->assignment_ID, $periodKeys);
        $editablePeriodKey = GradingTerm::currentEditablePeriodKeyForSection($section, $semester);
        $editablePeriodLabel = GradingTerm::currentEditablePeriodLabelForSection($section, $semester);
        $grades = StudentSubjectGrade::query()
            ->with('studentSubject')
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereHas('studentSubject', fn ($query) => $query->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID')))
            ->get()
            ->groupBy(fn (StudentSubjectGrade $grade) => $grade->studentSubject?->enrollment_ID)
            ->map(fn ($items) => $items->keyBy('grading_period'));

        $editablePeriodGrades = collect($editablePeriodKeys)
            ->flatMap(fn (string $periodKey) => $grades->flatten(1)->where('grading_period', $periodKey));

        $canEditCurrentTerm = $this->isGradingInputOpen($section, $semester)
            && $editablePeriodKeys !== []
            && ($editablePeriodGrades->isEmpty() || $editablePeriodGrades->contains(fn (StudentSubjectGrade $grade): bool => ! $grade->isTeacherLocked()));

        $summaries = $this->buildSummaries($enrollments, $grades, $periods);

        return view('users.teacher.sections.show', [
            'assignment' => $assignment,
            'section' => $section,
            'enrollments' => $enrollments,
            'periods' => $periods,
            'inputPeriods' => $inputPeriods,
            'grades' => $grades,
            'summaries' => $summaries,
            'lockedPeriodKeys' => $lockedPeriodKeys,
            'editablePeriodKey' => $editablePeriodKey,
            'editablePeriodLabel' => $editablePeriodLabel,
            'canEditCurrentTerm' => $canEditCurrentTerm,
        ]);
    }

    public function storeGrades(StoreTeacherSectionGradesRequest $request, TeacherSubjectAssignment $assignment): RedirectResponse
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load('section.gradeLevel', 'curriculumSubject');
        $section = $assignment->section;
        $semester = $assignment->curriculumSubject?->semester;
        $periods = $this->gradingInputPeriods($section, $semester);
        $periodKeys = array_column($periods, 'key');
        $editablePeriodKeys = array_flip(array_intersect(
            AssignmentGradeTermUnlocker::editablePeriodKeysFor($assignment->assignment_ID),
            $periodKeys
        ));

        $enrollmentIds = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->pluck('enrollment_ID')
            ->toArray();

        if (! $this->isGradingInputOpen($section, $semester)) {
            return back()->withErrors(['grades' => 'This grading term is closed for teacher input.']);
        }

        if ($editablePeriodKeys === []) {
            return back()->withErrors(['grades' => 'No grading term is currently open for input.']);
        }

        $gradesInput = $request->input('grades', []);

        foreach ($gradesInput as $enrollmentId => $periodData) {
            if (! in_array((int) $enrollmentId, $enrollmentIds, true)) {
                continue;
            }

            foreach ($periodKeys as $periodKey) {
                if (! isset($editablePeriodKeys[$periodKey])) {
                    continue;
                }

                $gradeValue = $periodData[$periodKey]['grade'] ?? null;
                $remarksValue = $periodData[$periodKey]['remarks'] ?? null;

                if ($gradeValue === null && $remarksValue === null) {
                    continue;
                }

                $studentSubject = StudentSubject::query()
                    ->where('enrollment_ID', $enrollmentId)
                    ->where('curr_subj_ID', $assignment->curr_subj_ID)
                    ->first();

                if (! $studentSubject) {
                    continue;
                }

                $existingGrade = StudentSubjectGrade::query()
                    ->where('student_subject_ID', $studentSubject->student_subject_ID)
                    ->where('assignment_ID', $assignment->assignment_ID)
                    ->forPeriodKey($periodKey)
                    ->first();

                if ($existingGrade?->isTeacherLocked()) {
                    continue;
                }

                if ($gradeValue !== null && $gradeValue !== '') {
                    $numericGrade = (float) $gradeValue;
                } else {
                    $numericGrade = null;
                }

                StudentSubjectGrade::query()->updateOrCreate(
                    [
                        'student_subject_ID' => $studentSubject->student_subject_ID,
                        'assignment_ID' => $assignment->assignment_ID,
                        'term_ID' => StudentSubjectGrade::termIdForPeriodKey($periodKey),
                    ],
                    [
                        'numeric_grade' => $numericGrade,
                        'remarks' => $remarksValue ?: null,
                        'status' => 'draft',
                        'submitted_at' => null,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'posted_by' => $request->user()->staff_id,
                    ]
                );
            }
        }

        if (! $request->boolean('submit')) {
            return back()->with('status', 'Grades saved successfully.');
        }

        $submitted = $this->submitSavedGrades($assignment, $enrollmentIds, array_keys($editablePeriodKeys));

        if (! $submitted) {
            return back()->withErrors(['grades' => 'No grades available to submit.'])->withInput();
        }

        return back()->with('status', 'Grades saved and submitted successfully.');
    }

    public function importClassList(Request $request, TeacherSubjectAssignment $assignment): RedirectResponse
    {
        $this->authorizeAssignment($request, $assignment);

        $validated = $request->validate([
            'class_list' => ['required', 'file', 'max:15360', 'mimes:csv,txt,xlsx,pdf'],
        ]);

        $assignment->load(['section.gradeLevel', 'section.academicYear', 'curriculumSubject']);
        $section = $assignment->section;

        if (! $section) {
            return back()->withErrors(['class_list' => 'This assignment has no section to import students into.']);
        }

        try {
            $rows = ClassListSpreadsheet::rowsFromUpload($validated['class_list']);
            $records = $this->classListRecords($rows);
        } catch (\Throwable $exception) {
            return back()->withErrors(['class_list' => $exception->getMessage()]);
        }

        if ($records === []) {
            return back()->withErrors(['class_list' => 'No valid learner rows were found. Make sure the file has LRN and learner name columns.']);
        }

        $result = DB::transaction(function () use ($records, $section, $assignment, $request): array {
            $enrollmentYear = (int) ($section->academicYear?->start_date?->year ?? now()->year);
            $createdStudents = 0;
            $updatedStudents = 0;
            $createdEnrollments = 0;
            $existingEnrollments = 0;
            $skipped = [];

            foreach ($records as $record) {
                $student = Student::query()->where('lrn', $record['lrn'])->first();
                $defaultPassword = StudentCredentials::defaultPassword(
                    $record['first_name'],
                    $record['last_name'],
                    $enrollmentYear,
                );

                if (! $student) {
                    $student = Student::query()->create([
                        'username' => StudentCredentials::usernameFromLrn($record['lrn']),
                        'password' => Hash::make($defaultPassword),
                        'change_password' => true,
                        'password_changed_at' => null,
                        'lrn' => $record['lrn'],
                        'first_name' => $record['first_name'],
                        'middle_name' => $record['middle_name'],
                        'last_name' => $record['last_name'],
                        'suffix' => $record['suffix'],
                        'sex' => $record['sex'],
                        'birthdate' => $record['birthdate'],
                        'mother_tongue' => $record['mother_tongue'],
                        'status' => 'approved',
                        'activated_by' => $request->user()?->staff_id,
                        'activated_at' => now(),
                    ]);
                    $createdStudents++;
                } else {
                    $updates = $this->missingStudentUpdates($student, $record);
                    if ($student->username === null && ! Student::query()->where('username', $record['lrn'])->whereKeyNot($student->id)->exists()) {
                        $updates['username'] = StudentCredentials::usernameFromLrn($record['lrn']);
                    }
                    if ($student->password === null) {
                        $updates['password'] = Hash::make($defaultPassword);
                        $updates['change_password'] = true;
                        $updates['password_changed_at'] = null;
                    }
                    if (! in_array($student->status, ['approved', 'active'], true)) {
                        $updates['status'] = 'approved';
                    }

                    if ($updates !== []) {
                        $student->update($updates);
                        $updatedStudents++;
                    }
                }

                $this->importStudentSf1Details($student, $record);

                $semester = in_array($section->grade_level, ['grade_11', 'grade_12'], true)
                    ? ($assignment->curriculumSubject?->semester ?? 'first')
                    : null;

                $conflictingEnrollment = Enrollment::query()
                    ->where('student_ID', $student->id)
                    ->where('SY_ID', $section->SY_ID)
                    ->when($semester, fn ($query) => $query->where('semester', $semester), fn ($query) => $query->whereNull('semester'))
                    ->where('section_ID', '!=', $section->section_ID)
                    ->first();

                if ($conflictingEnrollment) {
                    $skipped[] = "{$record['lrn']} is already enrolled in another section for this school year.";

                    continue;
                }

                $enrollment = Enrollment::query()->firstOrCreate(
                    [
                        'student_ID' => $student->id,
                        'section_ID' => $section->section_ID,
                        'SY_ID' => $section->SY_ID,
                        'semester' => $semester,
                    ],
                    [
                        'cluster_ID' => $section->cluster_ID,
                        'course_ID' => null,
                        'grade_ID' => $section->grade_ID,
                        'learner_type' => 'regular',
                        'enrollment_status' => EnrollmentStatus::ENROLLED,
                    ]
                );

                $enrollment->wasRecentlyCreated ? $createdEnrollments++ : $existingEnrollments++;
            }

            return compact('createdStudents', 'updatedStudents', 'createdEnrollments', 'existingEnrollments', 'skipped');
        });

        $message = "Class list import complete. Students created: {$result['createdStudents']}. Students updated: {$result['updatedStudents']}. Enrollments added: {$result['createdEnrollments']}. Already enrolled here: {$result['existingEnrollments']}.";

        return back()
            ->with('status', $message)
            ->with('class_list_import_warnings', $result['skipped']);
    }

    public function importAdvisoryClassList(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeAdvisorySection($request, $section);

        $validated = $request->validate([
            'class_list' => ['required', 'file', 'max:15360', 'mimes:csv,txt,xlsx,pdf'],
        ]);

        $section->load(['gradeLevel', 'academicYear']);

        try {
            $records = $this->classListRecords(ClassListSpreadsheet::rowsFromUpload($validated['class_list']));
        } catch (\Throwable $exception) {
            return back()->withErrors(['class_list' => $exception->getMessage()]);
        }

        if ($records === []) {
            return back()->withErrors(['class_list' => 'No valid learner rows were found. The file must include each learner\'s 12-digit LRN and name.']);
        }

        $result = $this->importRecordsIntoSection($records, $section, $request);
        $message = "Student import complete. Students created: {$result['createdStudents']}. Students updated: {$result['updatedStudents']}. Enrollments added: {$result['createdEnrollments']}. Already enrolled here: {$result['existingEnrollments']}.";

        return back()->with('status', $message)->with('class_list_import_warnings', $result['skipped']);
    }

    private function importRecordsIntoSection(array $records, Section $section, Request $request): array
    {
        return DB::transaction(function () use ($records, $section, $request): array {
            $enrollmentYear = (int) ($section->academicYear?->start_date?->year ?? now()->year);
            $createdStudents = 0;
            $updatedStudents = 0;
            $createdEnrollments = 0;
            $existingEnrollments = 0;
            $skipped = [];

            foreach ($records as $record) {
                $student = Student::query()->where('lrn', $record['lrn'])->first();
                $defaultPassword = StudentCredentials::defaultPassword($record['first_name'], $record['last_name'], $enrollmentYear);

                if (! $student) {
                    // Email is intentionally not imported. Many SF-1 lists have no
                    // email, and a shared guardian email must never block accounts.
                    $student = Student::query()->create([
                        'username' => StudentCredentials::usernameFromLrn($record['lrn']),
                        'password' => Hash::make($defaultPassword),
                        'change_password' => true,
                        'password_changed_at' => null,
                        'lrn' => $record['lrn'],
                        'first_name' => $record['first_name'],
                        'middle_name' => $record['middle_name'],
                        'last_name' => $record['last_name'],
                        'suffix' => $record['suffix'],
                        'sex' => $record['sex'],
                        'birthdate' => $record['birthdate'],
                        'mother_tongue' => $record['mother_tongue'],
                        'status' => 'approved',
                        'activated_by' => $request->user()?->staff_id,
                        'activated_at' => now(),
                    ]);
                    $createdStudents++;
                } else {
                    $updates = $this->missingStudentUpdates($student, $record);
                    if ($student->username === null && ! Student::query()->where('username', StudentCredentials::usernameFromLrn($record['lrn']))->whereKeyNot($student->id)->exists()) {
                        $updates['username'] = StudentCredentials::usernameFromLrn($record['lrn']);
                    }
                    if ($student->password === null) {
                        $updates['password'] = Hash::make($defaultPassword);
                        $updates['change_password'] = true;
                        $updates['password_changed_at'] = null;
                    }
                    if (! in_array($student->status, ['approved', 'active'], true)) {
                        $updates['status'] = 'approved';
                    }
                    if ($updates !== []) {
                        $student->update($updates);
                        $updatedStudents++;
                    }
                }

                $this->importStudentSf1Details($student, $record);

                $conflictingEnrollment = Enrollment::query()
                    ->where('student_ID', $student->id)
                    ->where('SY_ID', $section->SY_ID)
                    ->where('section_ID', '!=', $section->section_ID)
                    ->first();
                if ($conflictingEnrollment) {
                    $skipped[] = "{$record['lrn']} is already enrolled in another section for this school year.";

                    continue;
                }

                $enrollment = Enrollment::query()->firstOrCreate(
                    [
                        'student_ID' => $student->id,
                        'section_ID' => $section->section_ID,
                        'SY_ID' => $section->SY_ID,
                        'semester' => null,
                    ],
                    [
                        'cluster_ID' => $section->cluster_ID,
                        'course_ID' => null,
                        'grade_ID' => $section->grade_ID,
                        'learner_type' => 'regular',
                        'enrollment_status' => EnrollmentStatus::ENROLLED,
                    ],
                );
                $enrollment->wasRecentlyCreated ? $createdEnrollments++ : $existingEnrollments++;
            }

            return compact('createdStudents', 'updatedStudents', 'createdEnrollments', 'existingEnrollments', 'skipped');
        });
    }

    public function submitGrades(Request $request, TeacherSubjectAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load('section.gradeLevel', 'curriculumSubject');
        $section = $assignment->section;
        $semester = $assignment->curriculumSubject?->semester;
        $enrollmentIds = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->pluck('enrollment_ID')
            ->toArray();

        $editablePeriodKeys = AssignmentGradeTermUnlocker::editablePeriodKeysFor($assignment->assignment_ID);

        if (! $this->isGradingInputOpen($section, $semester)) {
            return back()->withErrors(['grades' => 'This grading term is closed for teacher input.']);
        }

        if ($editablePeriodKeys === []) {
            return back()->withErrors(['grades' => 'No grading term is currently open for submission.']);
        }

        $updated = $this->submitSavedGrades($assignment, $enrollmentIds, $editablePeriodKeys);

        if (! $updated) {
            return back()->withErrors(['grades' => 'No grades available to submit.'])->withInput();
        }

        return back()->with('status', 'Grades submitted successfully.');
    }

    /**
     * @param  list<int>  $enrollmentIds
     * @param  list<string>  $periodKeys
     */
    private function submitSavedGrades(TeacherSubjectAssignment $assignment, array $enrollmentIds, array $periodKeys): int
    {
        return StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereHas('studentSubject', fn ($query) => $query->whereIn('enrollment_ID', $enrollmentIds))
            ->whereIn('term_ID', array_map(StudentSubjectGrade::termIdForPeriodKey(...), $periodKeys))
            ->whereStatus(GradeStatus::teacherEditableSlugs())
            ->where(function ($query): void {
                $query->whereNotNull('numeric_grade')
                    ->orWhereNotNull('remarks');
            })
            ->update([
                'grade_status_ID' => GradeStatus::idFor(GradeStatus::SUBMITTED),
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
    }

    public function summaryPrint(Request $request, TeacherSubjectAssignment $assignment): View
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load(['section.gradeLevel', 'curriculumSubject.subject']);
        $section = $assignment->section;
        $semester = $assignment->curriculumSubject?->semester;
        $enrollments = Enrollment::query()
            ->with(['student'])
            ->where('section_ID', $section->section_ID)
            ->orderBy('enrollment_status_ID')
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $this->studentSortKey($enrollment))
            ->values();

        $periods = $this->gradingInputPeriods($section, $semester);
        $grades = StudentSubjectGrade::query()
            ->with('studentSubject')
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereHas('studentSubject', fn ($query) => $query->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID')))
            ->get()
            ->groupBy(fn (StudentSubjectGrade $grade) => $grade->studentSubject?->enrollment_ID)
            ->map(fn ($items) => $items->keyBy('grading_period'));

        $summaries = $this->buildSummaries($enrollments, $grades, $periods);

        return view('users.teacher.sections.summary-print', [
            'assignment' => $assignment,
            'section' => $section,
            'periods' => $periods,
            'summaries' => $summaries,
        ]);
    }

    private function periodsForSection(Section $section): array
    {
        return GradingTerm::periodsForSection($section);
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function gradingInputPeriods(?Section $section = null, ?string $semester = null): array
    {
        $editableKey = GradingTerm::currentEditablePeriodKeyForSection($section, $semester);

        if ($editableKey === null) {
            return [];
        }

        return array_values(array_filter(
            GradingTerm::openPeriodsForSection($section, $semester),
            fn (array $period): bool => $period['key'] === $editableKey,
        ));
    }

    private function isGradingInputOpen(?Section $section, ?string $semester = null): bool
    {
        if (! GradingTerm::isSeniorHighSection($section)) {
            return GradingTerm::isCurrentJuniorHighPeriodOpen();
        }

        $period = GradingTerm::currentSeniorHighPeriod();

        return (! $semester || $period['semester'] === $semester) && GradingTerm::isCurrentSeniorHighPeriodOpen();
    }

    private function buildSummaries($enrollments, $grades, array $periods): array
    {
        $periodKeys = array_column($periods, 'key');
        $summaries = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            $application = $student?->application;
            $name = $application
                ? $application->last_name.', '.$application->first_name
                : ($student?->user?->name ?? 'N/A');
            $gradeSet = $grades[$enrollment->enrollment_ID] ?? collect();
            $values = [];

            foreach ($periodKeys as $key) {
                $value = $gradeSet->get($key)?->numeric_grade;
                if ($value !== null) {
                    $values[] = (float) $value;
                }
            }

            $average = count($values) ? round(array_sum($values) / count($values), 2) : null;

            $summaries[$enrollment->enrollment_ID] = [
                'average' => $average,
                'grades' => $gradeSet,
                'name' => $name,
            ];
        }

        return $summaries;
    }

    private function studentSortKey(Enrollment $enrollment): string
    {
        $student = $enrollment->student;
        $application = $student?->application;
        $sexRank = match (strtolower((string) $student?->sex)) {
            'male' => 0,
            'female' => 1,
            default => 2,
        };

        return sprintf(
            '%d-%s-%s-%s',
            $sexRank,
            strtolower((string) ($application?->last_name ?? '')),
            strtolower((string) ($application?->first_name ?? '')),
            strtolower((string) ($student?->lrn ?? ''))
        );
    }

    private function authorizeAssignment(Request $request, TeacherSubjectAssignment $assignment): void
    {
        $staffId = $request->user()?->staff_id;

        if (! $staffId || $assignment->staff_ID !== $staffId) {
            abort(403);
        }
    }

    private function authorizeAdvisorySection(Request $request, Section $section): void
    {
        $staffId = $request->user()?->staff_id;

        if (! $staffId || $section->staff_ID !== $staffId) {
            abort(403);
        }
    }

    private function classListRecords(array $rows): array
    {
        [$headerIndex, $headerMap] = $this->classListHeader($rows);
        $records = [];

        foreach (array_slice($rows, $headerIndex + 1) as $row) {
            $record = $this->classListRecord($row, $headerMap);
            if ($record) {
                $records[$record['lrn']] = $record;
            }
        }

        return array_values($records);
    }

    private function classListHeader(array $rows): array
    {
        foreach (array_slice($rows, 0, 15, true) as $index => $row) {
            $map = [];
            foreach ($row as $column => $heading) {
                $key = $this->classListHeadingKey($heading);
                if ($key) {
                    $map[$key] = $column;
                }
            }

            if (isset($map['lrn']) && (isset($map['full_name']) || isset($map['first_name']) || isset($map['last_name']))) {
                return [$index, $map];
            }
        }

        throw new \RuntimeException('Could not find the class list header. Required columns: LRN and learner name.');
    }

    private function classListRecord(array $row, array $map): ?array
    {
        $lrn = $this->lrnValue($this->mappedValue($row, $map, 'lrn'));
        if (strlen($lrn) !== 12) {
            return null;
        }

        $name = $this->nameParts($row, $map);
        if ($name['first_name'] === '' || $name['last_name'] === '') {
            return null;
        }

        return [
            'lrn' => $lrn,
            'first_name' => $name['first_name'],
            'middle_name' => $name['middle_name'] !== '' ? $name['middle_name'] : null,
            'last_name' => $name['last_name'],
            'suffix' => $name['suffix'] !== '' ? $name['suffix'] : null,
            'sex' => $this->sexValue($this->mappedValue($row, $map, 'sex')),
            'birthdate' => $this->dateValue($this->mappedValue($row, $map, 'birthdate')),
            'mother_tongue' => $this->nullableText($this->mappedValue($row, $map, 'mother_tongue')),
            'ip_community' => $this->nullableText($this->mappedValue($row, $map, 'ip_community')),
            'address' => [
                'house_no' => $this->nullableText($this->mappedValue($row, $map, 'house_no')),
                'barangay' => $this->nullableText($this->mappedValue($row, $map, 'barangay')),
                'municipality' => $this->nullableText($this->mappedValue($row, $map, 'municipality')),
                'province' => $this->nullableText($this->mappedValue($row, $map, 'province')),
            ],
            'guardians' => [
                'father' => $this->personNameParts($this->mappedValue($row, $map, 'father_name')),
                'mother' => $this->personNameParts($this->mappedValue($row, $map, 'mother_name')),
                'guardian' => $this->personNameParts($this->mappedValue($row, $map, 'guardian_name')),
            ],
            'guardian_contact_no' => $this->nullableText($this->mappedValue($row, $map, 'guardian_contact_no')),
        ];
    }

    private function lrnValue(string $value): string
    {
        $value = trim($value);

        // Excel commonly renders 12-digit LRNs as values such as
        // 1.11111111111E11. Convert the numeric value before stripping
        // formatting characters so the exponent is not mistaken for digits.
        if (preg_match('/^\d+(?:\.\d+)?[eE][+-]?\d+$/', $value) && is_numeric($value)) {
            $value = sprintf('%.0f', (float) $value);
        }

        return preg_replace('/\D/', '', $value) ?? '';
    }

    private function classListHeadingKey(string $heading): ?string
    {
        $normalized = Str::of($heading)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->value();

        return match (true) {
            str_contains($normalized, 'lrn') || str_contains($normalized, 'learner reference') => 'lrn',
            // DepEd SF-1 uses "NAME (Last Name, First Name, Middle Name)"
            // as one learner-name column, not three separate columns.
            str_starts_with($normalized, 'name')
                && str_contains($normalized, 'last name')
                && str_contains($normalized, 'first name') => 'full_name',
            str_contains($normalized, 'first name') || str_contains($normalized, 'given name') => 'first_name',
            str_contains($normalized, 'middle name') => 'middle_name',
            str_contains($normalized, 'last name') || str_contains($normalized, 'surname') => 'last_name',
            str_contains($normalized, 'extension') || str_contains($normalized, 'suffix') => 'suffix',
            str_starts_with($normalized, 'sex') || str_contains($normalized, 'gender') => 'sex',
            str_contains($normalized, 'birth date') || str_contains($normalized, 'date of birth') => 'birthdate',
            str_contains($normalized, 'mother tongue') => 'mother_tongue',
            str_contains($normalized, 'ethnic group') || str_contains($normalized, 'indigenous cultural community') => 'ip_community',
            str_contains($normalized, 'house') || str_contains($normalized, 'street') || str_contains($normalized, 'sitio') || str_contains($normalized, 'purok') => 'house_no',
            str_contains($normalized, 'barangay') => 'barangay',
            str_contains($normalized, 'municipality') || str_contains($normalized, 'city') => 'municipality',
            str_contains($normalized, 'province') => 'province',
            str_contains($normalized, 'father') && str_contains($normalized, 'name') => 'father_name',
            (str_contains($normalized, 'mother') && str_contains($normalized, 'maiden')) => 'mother_name',
            str_contains($normalized, 'guardian') && str_contains($normalized, 'name') => 'guardian_name',
            str_contains($normalized, 'contact') || str_contains($normalized, 'mobile') || str_contains($normalized, 'telephone') => 'guardian_contact_no',
            (str_contains($normalized, 'learner') && str_contains($normalized, 'name'))
                || $normalized === 'name'
                || str_contains($normalized, 'name of learner') => 'full_name',
            default => null,
        };
    }

    private function nameParts(array $row, array $map): array
    {
        $firstName = $this->mappedValue($row, $map, 'first_name');
        $middleName = $this->mappedValue($row, $map, 'middle_name');
        $lastName = $this->mappedValue($row, $map, 'last_name');
        $suffix = $this->mappedValue($row, $map, 'suffix');

        if (($firstName === '' || $lastName === '') && isset($map['full_name'])) {
            $fullName = $this->mappedValue($row, $map, 'full_name');
            if (str_contains($fullName, ',')) {
                [$lastName, $rest] = array_pad(array_map('trim', explode(',', $fullName, 2)), 2, '');
                $parts = preg_split('/\s+/', $rest, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $firstName = array_shift($parts) ?? '';
                $middleName = implode(' ', $parts);
            } else {
                $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $lastName = array_pop($parts) ?? '';
                $firstName = array_shift($parts) ?? '';
                $middleName = implode(' ', $parts);
            }
        }

        return [
            'first_name' => Str::title($firstName),
            'middle_name' => Str::title($middleName),
            'last_name' => Str::title($lastName),
            'suffix' => Str::upper($suffix),
        ];
    }

    private function mappedValue(array $row, array $map, string $key): string
    {
        if (! isset($map[$key])) {
            return '';
        }

        return trim((string) ($row[$map[$key]] ?? ''));
    }

    private function sexValue(string $value): ?string
    {
        $normalized = Str::lower(trim($value));

        return match (true) {
            in_array($normalized, ['m', 'male'], true) => 'male',
            in_array($normalized, ['f', 'female'], true) => 'female',
            default => null,
        };
    }

    private function dateValue(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $days = (int) $value;
            if ($days > 20000) {
                return now()->setDate(1899, 12, 30)->addDays($days)->toDateString();
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableText(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : Str::title($value);
    }

    /** @return array{first_name: ?string, middle_name: ?string, last_name: ?string, suffix: ?string} */
    private function personNameParts(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['first_name' => null, 'middle_name' => null, 'last_name' => null, 'suffix' => null];
        }

        $parts = array_map('trim', explode(',', $value));
        if (count($parts) >= 2) {
            $lastName = array_shift($parts);
            $firstName = array_shift($parts) ?? '';
            $middleName = implode(' ', $parts);
        } else {
            $words = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $lastName = array_pop($words) ?? '';
            $firstName = array_shift($words) ?? '';
            $middleName = implode(' ', $words);
        }

        return [
            'first_name' => $firstName !== '' ? Str::title($firstName) : null,
            'middle_name' => $middleName !== '' ? Str::title($middleName) : null,
            'last_name' => $lastName !== '' ? Str::title($lastName) : null,
            'suffix' => null,
        ];
    }

    private function importStudentSf1Details(Student $student, array $record): void
    {
        if (filled($record['ip_community'])) {
            $profile = StudentProfile::query()->firstOrNew(
                ['student_ID' => $student->id],
            );
            if (! $profile->is_ip) {
                $profile->is_ip = true;
            }
            if (! filled($profile->ip_community)) {
                $profile->ip_community = $record['ip_community'];
            }
            $profile->save();
        }

        $address = array_filter($record['address'], fn ($value) => filled($value));
        if ($address !== []) {
            $currentAddress = StudentAddress::query()->firstOrNew([
                'student_ID' => $student->id,
                'address_type' => 'current',
            ]);
            foreach ($address as $field => $value) {
                if (! filled($currentAddress->{$field})) {
                    $currentAddress->{$field} = $value;
                }
            }
            $currentAddress->save();
        }

        foreach ($record['guardians'] as $relationship => $attributes) {
            if (! filled($attributes['first_name']) || ! filled($attributes['last_name'])) {
                continue;
            }

            $guardian = StudentGuardian::query()->firstOrNew([
                'student_ID' => $student->id,
                'relationship' => $relationship,
            ]);
            foreach ($attributes as $field => $value) {
                if (filled($value) && ! filled($guardian->{$field})) {
                    $guardian->{$field} = $value;
                }
            }
            if ($relationship === 'guardian' && filled($record['guardian_contact_no']) && ! filled($guardian->contact_no)) {
                $guardian->contact_no = $record['guardian_contact_no'];
            }
            $guardian->save();
        }
    }

    private function missingStudentUpdates(Student $student, array $record): array
    {
        $updates = [];
        foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'sex', 'birthdate', 'mother_tongue'] as $field) {
            if (($student->{$field} === null || $student->{$field} === '') && ! empty($record[$field])) {
                $updates[$field] = $record[$field];
            }
        }

        return $updates;
    }
}
