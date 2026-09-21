<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\CurriculumSubject;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\Section;
use App\Models\Staff;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use App\Support\TeacherGradeNotifier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $clusterId = $request->string('cluster_ID')->toString();
        $schoolYearId = $request->string('SY_ID')->toString();
        $perPage = (int) $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        $advisoryPerPage = (int) $request->integer('advisory_per_page', 10);
        if (! in_array($advisoryPerPage, [10, 15, 25, 50], true)) {
            $advisoryPerPage = 10;
        }

        $advisoryGradeLevel = $request->string('advisory_grade_level')->toString();
        $advisoryGradeId = GradeLevel::idForValue($advisoryGradeLevel);
        $advisorySchoolYearId = $request->string('advisory_SY_ID')->toString();
        $advisorySearch = trim($request->string('advisory_search')->toString());

        $assignments = TeacherSubjectAssignment::query()
            ->with(['section.cluster', 'section.gradeLevel', 'section.academicYear', 'curriculumSubject.subject', 'staff.role'])
            ->withCount([
                'grades as locked_grades_count' => function ($query): void {
                    $query->whereStatus([GradeStatus::SUBMITTED, GradeStatus::APPROVED]);
                },
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->whereHas('section', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('curriculumSubject.subject', function ($q) use ($search): void {
                            $q->where('title', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('staff', function ($q) use ($search): void {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($gradeId, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('grade_ID', $gradeId)))
            ->when($clusterId !== '', fn ($q) => $q->whereHas('section', fn ($s) => $s->where('cluster_ID', $clusterId)))
            ->when($schoolYearId !== '', fn ($q) => $q->where('SY_ID', $schoolYearId))
            ->orderByDesc('updated_at')
            ->paginate($perPage, ['*'], 'subject_page')
            ->withQueryString();

        $sections = Section::query()
            ->with(['cluster', 'gradeLevel', 'academicYear', 'adviser'])
            ->orderBy('grade_ID')
            ->orderBy('name')
            ->get();

        $advisorySections = Section::query()
            ->with(['cluster', 'gradeLevel', 'academicYear', 'adviser'])
            ->withCount(['enrollments as active_enrollments_count' => function ($query): void {
                $query->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->whereNotNull('staff_ID')
            ->when($advisorySearch !== '', function ($query) use ($advisorySearch): void {
                $query->where(function ($inner) use ($advisorySearch): void {
                    $inner->where('name', 'like', "%{$advisorySearch}%")
                        ->orWhereHas('adviser', function ($teacherQuery) use ($advisorySearch): void {
                            $teacherQuery->where('first_name', 'like', "%{$advisorySearch}%")
                                ->orWhere('last_name', 'like', "%{$advisorySearch}%");
                        });
                });
            })
            ->when($advisoryGradeId, fn ($query) => $query->where('grade_ID', $advisoryGradeId))
            ->when($advisorySchoolYearId !== '', fn ($query) => $query->where('SY_ID', $advisorySchoolYearId))
            ->orderByDesc('SY_ID')
            ->orderBy('grade_ID')
            ->orderBy('name')
            ->paginate($advisoryPerPage, ['*'], 'advisory_page')
            ->withQueryString();

        $curriculumSubjects = CurriculumSubject::query()
            ->with(['subject', 'gradeLevel', 'gradingSemester', 'curriculumGradeLevel'])
            ->join('curriculum_grade_levels', 'curriculum_subjects.curriculum_grade_level_ID', '=', 'curriculum_grade_levels.curriculum_ID')
            ->orderBy('curriculum_grade_levels.grade_ID')
            ->orderBy('curriculum_grade_levels.semester_ID')
            ->select('curriculum_subjects.*')
            ->get();

        $subjectAssignmentRows = TeacherSubjectAssignment::query()
            ->with(['section.gradeLevel', 'section.academicYear', 'curriculumSubject.subject', 'staff'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->whereHas('section', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('curriculumSubject.subject', fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('staff', fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
                });
            })
            ->when($gradeId, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('grade_ID', $gradeId)))
            ->when($clusterId !== '', fn ($q) => $q->whereHas('section', fn ($s) => $s->where('cluster_ID', $clusterId)))
            ->when($schoolYearId !== '', fn ($q) => $q->where('SY_ID', $schoolYearId))
            ->get()
            ->groupBy('curr_subj_ID');

        $subjectRows = $curriculumSubjects
            ->when($gradeLevel !== '', fn ($items) => $items->where('grade_ID', $gradeId))
            ->when($clusterId !== '', fn ($items) => $items->where('cluster_ID', $clusterId))
            ->groupBy('subject_ID')
            ->map(function ($subjectCurriculumRows) use ($subjectAssignmentRows, $search) {
                $allAssignments = $subjectCurriculumRows
                    ->flatMap(fn ($row) => $subjectAssignmentRows->get($row->curr_subj_ID, collect()));
                $subject = $subjectCurriculumRows->first()->subject;

                return (object) [
                    'subject' => $subject,
                    'assignments' => $allAssignments,
                    'matches_search' => $search === '' || str_contains(strtolower(($subject?->code ?? '').' '.($subject?->title ?? '')), strtolower($search)) || $allAssignments->isNotEmpty(),
                ];
            })
            ->filter(fn ($row) => $row->matches_search)
            ->sortBy(fn ($row) => $row->subject?->code ?? '')
            ->values();

        $subjectPage = LengthAwarePaginator::resolveCurrentPage('subject_page');
        $subjectRows = new LengthAwarePaginator(
            $subjectRows->forPage($subjectPage, $perPage)->values(),
            $subjectRows->count(),
            $perPage,
            $subjectPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'subject_page']
        );
        $subjectRows->withQueryString();

        $teachers = Staff::query()
            ->whereHas('role', fn ($q) => $q->where('role_name', 'teacher'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $academicYears = AcademicYear::query()->orderByDesc('school_year')->get(['SY_ID', 'school_year']);
        $clusters = Cluster::query()->orderBy('name')->get(['cluster_ID', 'name']);

        return view('users.admin.teacher-assignments', [
            'assignments' => $assignments,
            'subjectRows' => $subjectRows,
            'sections' => $sections,
            'advisorySections' => $advisorySections,
            'curriculumSubjects' => $curriculumSubjects,
            'teachers' => $teachers,
            'academicYears' => $academicYears,
            'clusters' => $clusters,
            'gradeLevels' => GradeLevel::options(),
            'perPage' => $perPage,
            'advisoryPerPage' => $advisoryPerPage,
            'advisoryCount' => Section::query()->whereNotNull('staff_ID')->count(),
            'unassignedSectionCount' => Section::query()->whereNull('staff_ID')->count(),
            'assignmentCount' => TeacherSubjectAssignment::query()->count(),
            'teacherCount' => $teachers->count(),
            'advisoryGradeLevel' => $advisoryGradeLevel,
            'advisorySchoolYearId' => $advisorySchoolYearId,
            'advisorySearch' => $advisorySearch,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_ID' => ['required', 'integer', 'exists:sections,section_ID'],
            'curr_subj_ID' => ['required', 'integer', 'exists:curriculum_subjects,curr_subj_ID'],
            'staff_ID' => ['required', 'integer', 'exists:staffs,staff_id'],
        ]);

        $section = Section::query()->findOrFail($validated['section_ID']);
        $curriculumSubject = CurriculumSubject::query()->findOrFail($validated['curr_subj_ID']);
        $teacher = Staff::query()
            ->where('staff_id', $validated['staff_ID'])
            ->whereHas('role', fn ($q) => $q->where('role_name', 'teacher'))
            ->first();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'staff_ID' => 'Selected staff is not a teacher.',
            ]);
        }

        if (! $this->curriculumSubjectMatchesSection($section, $curriculumSubject)) {
            throw ValidationException::withMessages([
                'curr_subj_ID' => 'Selected subject does not belong to the section curriculum and grade level.',
            ]);
        }

        TeacherSubjectAssignment::query()->updateOrCreate(
            [
                'section_ID' => $section->section_ID,
                'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
            ],
            [
                'staff_ID' => $validated['staff_ID'],
                'SY_ID' => $section->SY_ID,
            ]
        );

        return back()->with('status', 'Assignment saved successfully.');
    }

    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'grade_levels' => ['required', 'array', 'min:1'],
            'grade_levels.*' => ['required', 'in:grade_7,grade_8,grade_9,grade_10,grade_11,grade_12'],
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['required', 'integer', 'exists:sections,section_ID'],
            'curr_subj_ids' => ['required', 'array', 'min:1'],
            'curr_subj_ids.*' => ['required', 'integer', 'exists:curriculum_subjects,curr_subj_ID'],
            'staff_ID' => ['required', 'integer', 'exists:staffs,staff_id'],
        ]);

        $teacher = Staff::query()
            ->where('staff_id', $validated['staff_ID'])
            ->whereHas('role', fn ($q) => $q->where('role_name', 'teacher'))
            ->first();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'staff_ID' => 'Selected staff is not a teacher.',
            ]);
        }

        $sections = Section::query()
            ->whereIn('section_ID', $validated['section_ids'])
            ->get()
            ->keyBy('section_ID');

        $subjects = CurriculumSubject::query()
            ->whereIn('curr_subj_ID', $validated['curr_subj_ids'])
            ->with('curriculumGradeLevel')
            ->get()
            ->keyBy('curr_subj_ID');

        if ($sections->count() !== count(array_unique($validated['section_ids']))) {
            throw ValidationException::withMessages([
                'section_ids' => 'One or more selected sections could not be found.',
            ]);
        }

        if ($subjects->count() !== count(array_unique($validated['curr_subj_ids']))) {
            throw ValidationException::withMessages([
                'curr_subj_ids' => 'One or more selected subjects could not be found.',
            ]);
        }

        $selectedGradeLevels = collect($validated['grade_levels']);
        $invalidSectionSelected = $sections->contains(function (Section $section) use ($selectedGradeLevels): bool {
            return ! $selectedGradeLevels->contains($section->grade_level);
        });

        if ($invalidSectionSelected) {
            throw ValidationException::withMessages([
                'section_ids' => 'Selected sections must belong to the checked grade levels.',
            ]);
        }

        $assigned = 0;
        $skipped = 0;

        foreach ($sections as $section) {
            foreach ($subjects as $subject) {
                if (! $this->curriculumSubjectMatchesSection($section, $subject)) {
                    $skipped++;

                    continue;
                }

                TeacherSubjectAssignment::query()->updateOrCreate(
                    [
                        'section_ID' => $section->section_ID,
                        'curr_subj_ID' => $subject->curr_subj_ID,
                    ],
                    [
                        'staff_ID' => $teacher->staff_id,
                        'SY_ID' => $section->SY_ID,
                    ]
                );
                $assigned++;
            }
        }

        if ($assigned === 0) {
            throw ValidationException::withMessages([
                'curr_subj_ids' => 'No compatible section and subject combinations were found for the selected bulk assignment.',
            ]);
        }

        $message = "Bulk assignment completed ({$assigned} assignment(s)).";
        if ($skipped > 0) {
            $message .= " {$skipped} incompatible combination(s) were skipped.";
        }

        return back()->with('status', $message);
    }

    public function update(Request $request, TeacherSubjectAssignment $assignment)
    {
        $validated = $request->validate([
            'staff_ID' => ['required', 'integer', 'exists:staffs,staff_id'],
        ]);

        $teacher = Staff::query()
            ->where('staff_id', $validated['staff_ID'])
            ->whereHas('role', fn ($q) => $q->where('role_name', 'teacher'))
            ->first();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'staff_ID' => 'Selected staff is not a teacher.',
            ]);
        }

        $assignment->update([
            'staff_ID' => $validated['staff_ID'],
        ]);

        return back()->with('status', 'Assignment updated successfully.');
    }

    public function assignAdvisory(Request $request)
    {
        $validated = $request->validate([
            'section_ID' => ['required', 'integer', 'exists:sections,section_ID'],
            'staff_ID' => ['required', 'integer', 'exists:staffs,staff_id'],
        ]);

        $section = Section::query()->findOrFail($validated['section_ID']);
        $teacher = Staff::query()
            ->where('staff_id', $validated['staff_ID'])
            ->whereHas('role', fn ($q) => $q->where('role_name', 'teacher'))
            ->first();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'staff_ID' => 'Selected staff is not a teacher.',
            ]);
        }

        $existingAdvisory = Section::query()
            ->where('staff_ID', $teacher->staff_id)
            ->where('SY_ID', $section->SY_ID)
            ->where('section_ID', '!=', $section->section_ID)
            ->first();

        if ($existingAdvisory) {
            throw ValidationException::withMessages([
                'staff_ID' => "This teacher is already adviser of {$existingAdvisory->name} for the same school year.",
            ]);
        }

        $section->update([
            'staff_ID' => $teacher->staff_id,
        ]);

        return back()->with('status', 'Advisory section assigned successfully.');
    }

    public function updateAdvisory(Request $request, Section $section)
    {
        $validated = $request->validate([
            'target_section_ID' => ['nullable', 'integer', 'exists:sections,section_ID'],
        ]);

        $teacher = $section->adviser;
        $targetSectionId = $validated['target_section_ID'] ?? null;

        if (! $targetSectionId || (int) $targetSectionId === (int) $section->section_ID) {
            $section->update(['staff_ID' => $targetSectionId ? $teacher?->staff_id : null]);

            return back()->with('status', $targetSectionId ? 'Advisory assignment updated successfully.' : 'Teacher is no longer assigned as an adviser.');
        }

        $targetSection = Section::query()->findOrFail($targetSectionId);

        if ($targetSection->staff_ID) {
            throw ValidationException::withMessages([
                'target_section_ID' => 'The selected section already has an adviser.',
            ]);
        }

        $existingAdvisory = Section::query()
            ->where('staff_ID', $teacher?->staff_id)
            ->where('SY_ID', $targetSection->SY_ID)
            ->where('section_ID', '!=', $section->section_ID)
            ->first();

        if ($existingAdvisory) {
            throw ValidationException::withMessages([
                'staff_ID' => "This teacher is already adviser of {$existingAdvisory->name} for the same school year.",
            ]);
        }

        DB::transaction(function () use ($section, $targetSection, $teacher): void {
            $section->update(['staff_ID' => null]);
            $targetSection->update(['staff_ID' => $teacher->staff_id]);
        });

        return back()->with('status', 'Advisory assignment updated successfully.');
    }

    public function unlockGrades(TeacherSubjectAssignment $assignment)
    {
        $updated = StudentSubjectGrade::query()
            ->where('assignment_ID', $assignment->assignment_ID)
            ->whereStatus(GradeStatus::teacherLockedSlugs())
            ->update([
                'grade_status_ID' => GradeStatus::idFor(GradeStatus::DRAFT),
                'submitted_at' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);

        if ($updated === 0) {
            return back()->with('status', 'No locked grades were found for this assignment.');
        }

        TeacherGradeNotifier::unlocked($assignment);

        return back()->with('status', "{$updated} grade record(s) unlocked. The teacher can now update and resubmit them.");
    }

    public function destroy(TeacherSubjectAssignment $assignment)
    {
        $assignment->delete();

        return back()->with('status', 'Assignment removed.');
    }

    private function curriculumSubjectMatchesSection(Section $section, CurriculumSubject $curriculumSubject): bool
    {
        if ($curriculumSubject->curriculum_ID !== $section->curriculum_grade_level_ID ||
            $curriculumSubject->grade_ID !== $section->grade_ID) {
            return false;
        }

        if ($section->cluster_ID && $curriculumSubject->cluster_ID && $section->cluster_ID !== $curriculumSubject->cluster_ID) {
            return false;
        }

        return true;
    }
}
