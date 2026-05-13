<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherSectionController extends Controller
{
    public function index(Request $request): View
    {
        $staff = $request->user();
        $assignments = collect();
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
            $assignments = TeacherSubjectAssignment::query()
                ->with([
                    'section' => function ($query): void {
                        $query->with(['academicYear', 'cluster', 'gradeLevel'])
                            ->withCount(['enrollments as active_enrollments_count' => function ($subQuery) {
                                $subQuery->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled']);
                            }]);
                    },
                    'curriculumSubject.subject',
                ])
                ->where('staff_ID', $staff->staff_id)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->whereHas('section', function ($sectionQuery) use ($search): void {
                            $sectionQuery->where('name', 'like', '%'.$search.'%');
                        })->orWhereHas('curriculumSubject.subject', function ($subjectQuery) use ($search): void {
                            $subjectQuery->where('title', 'like', '%'.$search.'%')
                                ->orWhere('code', 'like', '%'.$search.'%');
                        });
                    });
                })
                ->when($gradeId, function ($query) use ($gradeId): void {
                    $query->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('grade_ID', $gradeId));
                })
                ->when($clusterId !== '', function ($query) use ($clusterId): void {
                    $query->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('cluster_ID', $clusterId));
                })
                ->when($schoolYearId !== '', function ($query) use ($schoolYearId): void {
                    $query->where('SY_ID', $schoolYearId);
                })
                ->orderBy('section_ID')
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('users.teacher.sections.index', [
            'assignments' => $assignments,
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

    public function show(Request $request, TeacherSubjectAssignment $assignment): View
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load(['section', 'curriculumSubject.subject']);
        $section = $assignment->section;
        $semester = $assignment->curriculumSubject?->semester;
        $isSeniorHigh = in_array($section?->grade_level, ['grade_11', 'grade_12'], true);

        $enrollments = Enrollment::query()
            ->with(['student'])
            ->where('section_ID', $section->section_ID)
            ->when($isSeniorHigh && $semester, function ($query) use ($semester): void {
                $query->where('semester', $semester);
            })
            ->orderBy('enrollment_status')
            ->get();

        $periods = $this->periodsForSection($section);
        $grades = StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID'))
            ->get()
            ->groupBy('enrollment_ID')
            ->map(fn ($items) => $items->keyBy('grading_period'));

        $summaries = $this->buildSummaries($enrollments, $grades, $periods);

        return view('users.teacher.sections.show', [
            'assignment' => $assignment,
            'section' => $section,
            'enrollments' => $enrollments,
            'periods' => $periods,
            'grades' => $grades,
            'summaries' => $summaries,
        ]);
    }

    public function storeGrades(Request $request, TeacherSubjectAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load('section', 'curriculumSubject');
        $section = $assignment->section;
        $semester = $assignment->curriculumSubject?->semester;
        $isSeniorHigh = in_array($section?->grade_level, ['grade_11', 'grade_12'], true);
        $periods = $this->periodsForSection($section);
        $periodKeys = array_column($periods, 'key');

        $enrollmentIds = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->when($isSeniorHigh && $semester, function ($query) use ($semester): void {
                $query->where('semester', $semester);
            })
            ->pluck('enrollment_ID')
            ->toArray();

        $gradesInput = $request->input('grades', []);

        foreach ($gradesInput as $enrollmentId => $periodData) {
            if (! in_array((int) $enrollmentId, $enrollmentIds, true)) {
                continue;
            }

            foreach ($periodKeys as $periodKey) {
                $gradeValue = $periodData[$periodKey]['grade'] ?? null;
                $remarksValue = $periodData[$periodKey]['remarks'] ?? null;

                if ($gradeValue === null && $remarksValue === null) {
                    continue;
                }

                if ($gradeValue !== null && $gradeValue !== '') {
                    if (! is_numeric($gradeValue)) {
                        return back()->withErrors(['grades' => 'Grades must be numeric.'])->withInput();
                    }

                    $numericGrade = (float) $gradeValue;
                    if ($numericGrade < 0 || $numericGrade > 100) {
                        return back()->withErrors(['grades' => 'Grades must be between 0 and 100.'])->withInput();
                    }
                } else {
                    $numericGrade = null;
                }

                StudentSubjectGrade::query()->updateOrCreate(
                    [
                        'enrollment_ID' => $enrollmentId,
                        'assignment_ID' => $assignment->assignment_ID,
                        'grading_period' => $periodKey,
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

        return back()->with('status', 'Grades saved successfully.');
    }

    

    public function submitGrades(Request $request, TeacherSubjectAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load('section', 'curriculumSubject');
        $section = $assignment->section;
        $semester = $assignment->curriculumSubject?->semester;
        $isSeniorHigh = in_array($section?->grade_level, ['grade_11', 'grade_12'], true);

        $enrollmentIds = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->when($isSeniorHigh && $semester, function ($query) use ($semester): void {
                $query->where('semester', $semester);
            })
            ->pluck('enrollment_ID')
            ->toArray();

        $updated = StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereIn('enrollment_ID', $enrollmentIds)
            ->where(function ($query): void {
                $query->whereNotNull('numeric_grade')
                    ->orWhereNotNull('remarks');
            })
            ->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);

        if (! $updated) {
            return back()->withErrors(['grades' => 'No grades available to submit for review.'])->withInput();
        }

        return back()->with('status', 'Grades submitted for registrar review.');
    }

    public function summaryPrint(Request $request, TeacherSubjectAssignment $assignment): View
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load(['section', 'curriculumSubject.subject']);
        $section = $assignment->section;
        $semester = $assignment->curriculumSubject?->semester;
        $isSeniorHigh = in_array($section?->grade_level, ['grade_11', 'grade_12'], true);

        $enrollments = Enrollment::query()
            ->with(['student'])
            ->where('section_ID', $section->section_ID)
            ->when($isSeniorHigh && $semester, function ($query) use ($semester): void {
                $query->where('semester', $semester);
            })
            ->orderBy('enrollment_status')
            ->get();

        $periods = $this->periodsForSection($section);
        $grades = StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID'))
            ->get()
            ->groupBy('enrollment_ID')
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
        $isJuniorHigh = in_array($section->grade_level, ['grade_7', 'grade_8', 'grade_9', 'grade_10'], true);

        if ($isJuniorHigh) {
            return [
                ['key' => 'q1', 'label' => 'Q1'],
                ['key' => 'q2', 'label' => 'Q2'],
                ['key' => 'q3', 'label' => 'Q3'],
                ['key' => 'q4', 'label' => 'Q4'],
            ];
        }

        return [
            ['key' => 'midterm', 'label' => 'Midterm'],
            ['key' => 'final', 'label' => 'Final'],
        ];
    }

    private function buildSummaries($enrollments, $grades, array $periods): array
    {
        $periodKeys = array_column($periods, 'key');
        $summaries = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            $application = $student?->application;
            $name = $application
                ? $application->last_name . ', ' . $application->first_name
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

    private function authorizeAssignment(Request $request, TeacherSubjectAssignment $assignment): void
    {
        $staffId = $request->user()?->staff_id;

        if (! $staffId || $assignment->staff_ID !== $staffId) {
            abort(403);
        }
    }
}
